<?php
namespace App\com\sprint\sms\api\controller;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \App\com\sprint\sms\api\bean\Response as Result;
use \App\com\sprint\sms\api\util\RequestUtil;
use \App\com\sprint\sms\api\support\FormatSupport as OBJ;
use \App\com\sprint\sms\api\bean\Page as Page;
use \App\com\sprint\sms\api\bean\dummy\Dummy1 as Dummy1;
use \App\com\sprint\sms\api\domain\Role as Role;
use \App\com\sprint\sms\api\domain\Audit;
use \App\com\sprint\sms\api\base\domain\BaseEntry as BaseEntry;
use \App\com\sprint\sms\api\base\domain\BaseEntry as BaseEntryVersion;
use \App\com\sprint\sms\api\util\DaoUtil as DaoUtil;
use \App\com\sprint\sms\api\util\ObjectUtil as ObjectUtil;
use \App\com\sprint\sms\api\base\BaseController as BaseController;
use \App\com\sprint\sms\api\service\AuditService;
use \App\com\sprint\sms\api\service\AccessService;

class TestController extends BaseController 
{
	
	public function object__cascade() 
	{
		$this->getLogger()->debug("object__cascade");
		$request = $this->getRequest();
		$obj = RequestUtil::paramsToObject($request, Dummy1::class);
		return Result::SUCCESS($obj->toFormatObject());
	}
	
	public function object__copy() 
	{
		$this->getLogger()->debug("object__copy");
		$dest = new Role();
		$src = new Role();
		$src->setName("Nama");
		$src->setActive(true);		
		$result = array();
		$result["DEST_BEFORE"] = $dest->toFormatObject();
		$result["SRC_BEFORE"] = $src->toFormatObject();
		ObjectUtil::copy($dest, $src, array("id", "version", "entryTime"), array("name" => array(ObjectUtil::NOT_NULL, ObjectUtil::NOT_EMPTY)));
		$result["DEST_AFTER"] = $dest->toFormatObject();
		$result["SRC_AFTER"] = $src->toFormatObject();
		return Result::SUCCESS($result);
	}
	
	
	
	public function convert__params() 
	{
		$this->getLogger()->debug("convert__params");
		$request = $this->getRequest();
		$obj = RequestUtil::paramsToObject($request, Role::class);
		return Result::SUCCESS($obj);
	}
	
	public function convert__body() 
	{
		$this->getLogger()->debug("convert__body");
		$request = $this->getRequest();
		$obj = RequestUtil::bodyToObject($request, Role::class);
		return Result::SUCCESS($obj);
	}
	
	public function convert__page() 
	{
		$this->getLogger()->debug("convert__page");
		$request = $this->getRequest();
		$page = RequestUtil::paramsToPage($request, Role::class);
		return Result::SUCCESS($page);
	}
	
	public function object__class() 
	{
		$this->getLogger()->debug("object__class");
		$obj = new \App\Entity\Role();
		return Result::SUCCESS(get_class($obj));
	}
	
	
	public function cache__get() 
	{
		$this->getLogger()->debug("cache__get");
		$request = $this->getRequest();
		$id = $request->getParam('id');
		$cache = $this->getCache();
		$val = $cache->get("TEST", $id, function() { return microtime(); });
		return Result::SUCCESS($val);
	}
	
	public function cache__get__0() 
	{
		$this->getLogger()->debug("cache__get__0");
		$request = $this->getRequest();
		$id = $request->getParam('id');
		$cache = $this->getCache();
		$val = $cache->get("TEST", $id);
		return Result::SUCCESS($val);
	}
	
	public function cache__remove() 
	{
		$this->getLogger()->debug("cache__remove");
		$request = $this->getRequest();
		$id = $request->getParam('id');
		$cache = $this->getCache();
		$val = $cache->remove("TEST", $id);
		return Result::SUCCESS($val);
	}
	
	public function cache__clear() 
	{
		$this->getLogger()->debug("cache__clear");
		$cache = $this->getCache();
		$cache->clear("TEST");
		return Result::SUCCESS();
	}
	
	
	
	public function object__classof() 
	{
		$this->getLogger()->debug("object__classof");
		$role = new Role();
		$val = ObjectUtil::isClassOf(get_class($role), BaseEntryVersion::class);
		return Result::SUCCESS($val);
	}
	
	public function audit__role() 
	{
		$this->getLogger()->debug("audit__role");
		$auditList = array();
		for ($i = 0; $i < 100; $i++) {
			$role = new Role();
			$role->setId($i);
			$role->setName("ROLE_" . $i);
			$audit = new Audit();
			$audit->setAuditor("TOM");
			$audit->setAction("ACTION");
			$audit->setClassName(get_class($role));
			$content = json_encode($role->toFormatObject());
			$audit->setContent($content);
			$audit->setAuditDate(new \DateTime());
			array_push($auditList, $audit);
		}		
		$auditService = new AuditService($this->getLogger(), $this->getEntityManager(), $auditList);
		$auditService->start();
		return Result::SUCCESS("CEK t_audit !!!");
	}
	
	
	public function api__private() 
	{
		$this->getLogger()->debug("api__private");
		$accessService = new AccessService($this->getLogger(), $this->getEntityManager(), $this->getCache(), $this->getSettings());
		$result = $accessService->getPrivateAccess();
		return Result::SUCCESS($result);
	}
	
	public function api__public() 
	{
		$this->getLogger()->debug("api__public");
		$accessService = new AccessService($this->getLogger(), $this->getEntityManager(), $this->getCache(), $this->getSettings());
		$result = $accessService->getPublicAccess();
		return Result::SUCCESS($result);
	}
	
}