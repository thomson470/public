<?php
namespace App\com\sprint\sms\api\util;

use \Psr\Http\Message\ServerRequestInterface as Request;

use ReflectionClass;
use ReflectionMethod;

use \App\com\sprint\sms\api\bean\Page;
use \App\com\sprint\sms\api\util\AppConstant;

use \App\com\sprint\sms\api\util\ObjectUtil;
use \App\com\sprint\sms\api\base\domain\BaseEntry;
use \App\com\sprint\sms\api\base\domain\BaseEntryVersion;

final class RequestUtil
{
	
	/*
	 * paramsToObject
	 *   - Untuk merubah request parameter menjadi Object.
	 *   - Method object yang akan diisi yang memiliki prefix 'set' -> setXXX()
	 *   - Huruf awal nama parameter diubah jadi huruf besar dan ditambahkan prefix 'set'
	 *   - Contoh: Parameter 'name' maka di class object akan dicari method 'setName'
	 */
	public static function paramsToObject(Request $request, $objectClass) 
	{
		$cls = new ReflectionClass($objectClass);
		$obj = $cls->newInstanceArgs();
		$params = $request->getParams();
		foreach($params as $key => $val) {
			if ($key !== AppConstant::PARAMETER_ACCESS_KEY &&
			    $key !== AppConstant::PARAMETER_PAGE_INDEX &&
				$key !== AppConstant::PARAMETER_PAGE_SIZE &&
				$key !== AppConstant::PARAMETER_ORDER) {				
				self::set($cls, $obj, $key, $val);
			}
		}
		return $obj;
	}
	
	/*
	 * paramsToPage
	 *   - Untuk merubah parameter request menjadi Page.	 
	 */
	public static function paramsToPage(Request $request, $objectClass) 
	{		
		$index = $request->getParam(AppConstant::PARAMETER_PAGE_INDEX);
		if ($index == null) {
			$index = 1;
		}
		$size = $request->getParam(AppConstant::PARAMETER_PAGE_SIZE);
		if ($size == null) {
			$size = AppConstant::PAGE_DEFAULT_SIZE;
		}
		$page = Page::CREATE((int)$index, (int)$size);
		//$entity = AppConstant::paramsToObject($request, $className);
		//$info = array();
		//$info[AppConstant::INFO_INPUT] = implode(",", $entity);
		//$page->setInfo($info);		
		return $page;
	}
	
	/*
	 * paramsToId
	 *   - Untuk mendapatkan parameter ID.	 
	 */
	public static function paramsToId(Request $request) 
	{
		return $request->getParam(AppConstant::PARAMETER_ID);
	}
	
	/*
	 * paramsToOrder
	 *	- Untuk membuat OrderBy dari parameter request
	 */
	public static function paramsToOrder(Request $request, $objectClass = null)
	{
		$order = $request->getParam(AppConstant::PARAMETER_ORDER);
		$order = $order !== null ? trim($order) : "";
		if ($order !== "") {
			$result = array();
			$exp = explode(AppConstant::SPLIT_ORDER_FIELD, $order);
			for ($i = 0; $i < count($exp); $i++) {
				$str = trim($exp[$i]);
				if ($str === "") {
					continue;
				}
				$split = explode(AppConstant::SPLIT_ORDER_SPEC, $str);
				$result[$split[0]] = count($split) > 1 ? strtoupper($split[1]) : "ASC";
			}
			if (count($result) !== 0) {
				return $result;
			}			
		}
		if ($objectClass !== null) {
			if (ObjectUtil::isClassOf($objectClass, BaseEntry::class) || 
				ObjectUtil::isClassOf($objectClass, BaseEntryVersion::class)) 
			{
				return array(BaseEntry::ENTRY => "DESC");
			}
		}
		return array();		
	}
	
	
	/*
	 * bodyToObject
	 *   - Untuk merubah request body menjadi Object.
	 * TODO: 
	 *   Saat ini masih support JSON, untuk tipe lain cari vendor pakai compose :D
	 */
	public static function bodyToObject(Request $request, $objectClass, $type = AppConstant::TYPE_JSON) 
	{
		$cls = new ReflectionClass($objectClass);
		$obj = $cls->newInstanceArgs();
		if ($type == AppConstant::TYPE_JSON) {
			$body = json_decode($request->getBody());
			foreach($body as $key => $val) {
				self::set($cls, $obj, $key, $val);
			}
		}
		return $obj;
	}
	
	
	/*
	 * getAccessKey
	 *   - Untuk mendapatkan Access Key.	 
	 */
	public static function getAccessKey(Request $request) {
		$accessKey = $request->getHeaderLine(AppConstant::HEADER_ACCESS_KEY);
		if ($accessKey !== null) {
			return $accessKey;
		}
		$accessKey = $request.getHeaderLine(strtolower(AppConstant::HEADER_ACCESS_KEY));
		if ($accessKey !== null) {
			return $accessKey;
		}
		$accessKey = $request.getParam(AppConstant::PARAMETER_ACCESS_KEY);
		return $accessKey;
	}
	
	/*
	 * getUserAgent
	 *   - Untuk mendapatkan User Agent.	 
	 */
	public static function getUserAgent(Request $request) {
		$userAgent = $request->getHeaderLine(AppConstant::HEADER_USER_AGENT_SLIM);
		if ($userAgent !== null) {
			return $userAgent;
		}
		$userAgent = $request->getHeaderLine(AppConstant::HEADER_USER_AGENT);
		if ($userAgent !== null) {
			return $userAgent;
		}
		$userAgent = $request->getHeaderLine(strtolower(AppConstant::HEADER_USER_AGENT));
		return $userAgent;
	}
	
	
	
	private static function set($cls, $obj, $key, $val) 
	{
		$exp = explode(AppConstant::SPLIT_OBJECT_FIELD, $key);
		$count = count($exp);
		if ($count === 1) {
			$exp[0] = trim($exp[0]);
			if ($exp[0] === "") {
				return;
			}
			$mtd = "set" . strtoupper(substr($exp[0], 0, 1)) . substr($exp[0], 1);
			if (!$cls->hasMethod($mtd)) {
				return;
			}	
			$ref = $cls->getMethod($mtd);
			$ref->invoke($obj, $val);
		} else {
			$tmp = array(array($obj, null)); // format: (object, class, field)
			for ($i = 0; $i < $count - 1; $i++) {
				$suffix = strtoupper(substr($exp[$i], 0, 1)) . substr($exp[$i], 1);
				$parentObj = $tmp[$i][0];
				$parentCls = new ReflectionClass(get_class($parentObj));
				if (!$parentCls->hasMethod("get" . $suffix)) {
					unset($tmp);
					return;
				}
				if (!$parentCls->hasMethod("set" . $suffix)) {
					unset($tmp);
					return;
				}
				$mtdGet = $parentCls->getMethod("get" . $suffix);
				$mtdSet = $parentCls->getMethod("set" . $suffix);
				$object = $mtdGet->invoke($parentObj);
				if ($object === null) {					
					$type = $mtdSet->getParameters()[0];
					if ($type->getClass() === null) {
						unset($tmp);
						return;
					}
					$object = self::tryCreateInstance($type->getClass()->name);
					if ($object === null) {
						unset($tmp);
						return;
					}
						
				}
				$tmp[$i][1] = $mtdSet;
				array_push($tmp, array($object, null));
			}
			$mtdSet = "set" . strtoupper(substr($exp[$count - 1], 0, 1)) . substr($exp[$count - 1], 1);
			$count = count($tmp);
			$object = $tmp[$count - 1][0];
			$class = new ReflectionClass(get_class($object));
			$method = $class->getMethod($mtdSet);
			$method->invoke($object, $val);
			for ($i = $count - 2; $i >= 0; $i--) {
				$tmp[$i][1]->invoke($tmp[$i][0], $tmp[$i + 1][0]);
			}			
			unset($tmp);
		}
		unset($exp);
	}
	
	private static function tryCreateInstance($cls) {
		try {
			$fcls = new ReflectionClass($cls);
			$fins = $fcls->newInstance();
			return $fins;
		} catch (Exception $e) {
			return false;
		}
	}
	
}