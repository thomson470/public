<?php
namespace App\com\sprint\sms\api\controller;

use ReflectionClass;
use \App\com\sprint\sms\api\util\RequestUtil;
use \App\com\sprint\sms\api\util\ObjectUtil;
use \App\com\sprint\sms\api\util\StringUtil;
use \App\com\sprint\sms\api\util\AppConstant;
use \App\com\sprint\sms\api\cache\Constant;
use \App\com\sprint\sms\api\bean\Response;
use \App\com\sprint\sms\api\domain\Access;
use \App\com\sprint\sms\api\domain\User;
use \App\com\sprint\sms\api\service\AccessService;
use \App\com\sprint\sms\api\base\BaseController;

class AccessController extends BaseController
{

	/*
	 * LOGIN
	 */
	public function login() {
		$request = $this->getRequest();
		$uname = $request->getParam("uname");
		if ($uname === null || trim($uname) === "") {
			return Response::ERROR_CODE("01", "uname is required");
		}
		$upass = $request->getParam("upass");
		if ($upass === null || trim($upass) === "") {
			return Response::ERROR_CODE("02", "upass is required");
		}
		$utime = $request->getParam("utime");
		if ($utime === null || trim($utime) === "") {
			return Response::ERROR_CODE("03", "utime is required");
		}
		$accessService = $this->getAccessService();
		$user = $accessService->getUser($uname);
		if ($user == null) {
			return Response::ERROR_CODE("04", "user is not found");
		}
		$gpass = $user->getName() . $utime . $user->getPassword();
		$gpass = hash("sha256", $gpass);
		if ($gpass !== $upass) {
			return Response::ERROR_CODE("05", "upass is not valid");
		}
		$role = $user->getRole();
		if ($role === null || !$role->getActive()) {
			return Response::ERROR_CODE("06", "Role is not active");
		}
		if (!$user->getActive()) {
			return Response::ERROR_CODE("07", "User is not active");
		}
		$accessService->removeAccessByUser($user);
		
		$agent = RequestUtil::getUserAgent($request);
		$access = $accessService->createAccess($user, $agent);
		
		// TODO: jika login harus diaudit, logikanya di sini.
		
		$user->setLastLoggedIn(new \DateTime());
		$accessService->updateUser($user);
		
		return Response::SUCCESS($access->getId());
	}
	
	
	/*
	 * LOGOUT
	 */
	public function logout() {
		$request = $this->getRequest();
		$accessKey = RequestUtil::getAccessKey($request);
		$accessService = $this->getAccessService();
		$access = $accessService->removeAccessById($accessKey);
		if ($access !== null) {
			// TODO: Audit Logout
			$user = $access->getUser();
			$name = $user !== null ? $user->getName() : null;
			if ($name !== null) {
				$user = $accessService->getUser($name);
				if ($user !== null) {
					$user->setLastLoggedOut(new \DateTime());
					$accessService->updateUser($user);
				}
			}			
		}
		return Response::SUCCESS();
	}
	
	/*
	 * MENU
	 */
	public function menu() {
		$request = $this->getRequest();
		$accessKey = RequestUtil::getAccessKey($request);
		$accessService = $this->getAccessService();
		$roleId = null;
		if ($accessKey !== null) {
			$access = $accessService->getAccess($accessKey);
			if ($access != null && !$access->hasExpired()) {
				$roleId = $access->getUser()->getRole()->getId();
			}
		}
		$menus = null;		
		$roleAccess = $accessService->getRoleAccess($roleId);		
		if ($roleAccess != null) {
			$menus = $roleAccess->getMenuList();
		} else {
			$menus = array();
		}
		return Response::SUCCESS($menus);
	}
	
	
	/*
	 * PROFILE
	 */
	public function profile() {
		$request = $this->getRequest();
		$accessKey = RequestUtil::getAccessKey($request);
		if ($accessKey === null) {
			return Response::ERROR_CODE("01", "Access key is required");
		}
		$accessService = $this->getAccessService();
		$access = $accessService->getAccess($accessKey);
		if ($access == null) {
			return Response::ERROR_CODE("02", "Access key is not found");
		}
		$user = $access->getUser();
		return Response::SUCCESS($user);
	}
	
	/*
	 * CACHE CLEAR
	 *   Untuk membersihkan/menghapus group cache object dari memory
	 */
	public function cache__clear() {
		$request = $this->getRequest();
		$group = $request->getParam("group");
		$group = $group != null ? strtoupper(trim($group)) : "";
		$list = explode(",", $group);
		$map = array();
		for ($i = 0; $i < count($list); $i++) {
			$grp = trim($list[$i]);
			if ($grp === "") {
				continue;
			}
			try {
				$this->getCache()->clear($grp);
				$map[$grp] = "SUCCESS";
			} catch (Exception $e) {
				$map[$grp] = "FAILED: " . $e->getMessage();
			}
		}
		return Response::SUCCESS($map);
	}
	
	/*
	 * API GROUP
	 *   Untuk mendapatkan daftar API group
	 */
	public function api__group() {
		$accessService = $this->getAccessService();
		$apiPrivate = $accessService->getPrivateAccess();
		$result = array_keys($apiPrivate);
		sort($result);
		return Response::SUCCESS($result);
	}
	
	/*
	 * API LIST
	 *   Untuk mendapatkan daftar API list
	 */
	public function api__list() {
		$request = $this->getRequest();
		$group = $request->getParam("group");
		if ($group === null) {
			return Response::ERROR_CODE("01", "Group is required");
		}
		$accessService = $this->getAccessService();
		$apiPrivate = $accessService->getPrivateAccess();
		if (!isset($apiPrivate[$group])) {
			return Response::SUCCESS();
		}
		$result = $apiPrivate[$group];
		return Response::SUCCESS($result);
	}	
	
	/*
	 * APP CONSTANT
	 *   Untuk mendapatkan konstanta yang ada di AppConstant
	 */
	public function app__constant() {
		$request = $this->getRequest();
		$name = $request->getParam("name");
		$name = $name !== null ? trim($name) : "";
		$prefix = $request->getParam("prefix");
		$prefix = $prefix !== null ? trim($prefix) : "";
		$cls = new ReflectionClass(AppConstant::class);
		$constant = $cls->getConstants();
		$result = array();
		if ($name !== "") {
			$result[$name] = $constant[$name];
		} else if ($prefix !== "") {
			foreach($constant as $key => $val) {
				if (!StringUtil::startsWith($key, $prefix)) {
					continue;
				}
				$result[$key] = $val;
			}
		} else {
			$result = $constant;
		}
		return Response::SUCCESS($result);
	}
	
	private function getAccessService() {
		return new AccessService($this->getLogger(), $this->getEntityManager(), $this->getCache(), $this->getSettings());
	}
	
}