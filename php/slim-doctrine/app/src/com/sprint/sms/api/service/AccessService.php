<?php
namespace App\com\sprint\sms\api\service;

use Exception;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \App\com\sprint\sms\api\bean\Response as Result;
use \Monolog\Logger;
use \Doctrine\ORM\EntityManager;
use \App\com\sprint\sms\api\support\FormatSupport;
use \App\com\sprint\sms\api\cache\Cache;
use \App\com\sprint\sms\api\domain\User;
use \App\com\sprint\sms\api\domain\Menu;
use \App\com\sprint\sms\api\domain\Role;
use \App\com\sprint\sms\api\domain\Access;
use \App\com\sprint\sms\api\dao\UserDao;
use \App\com\sprint\sms\api\dao\MenuDao;
use \App\com\sprint\sms\api\dao\RoleDao;
use \App\com\sprint\sms\api\dao\AccessDao;
use \App\com\sprint\sms\api\dao\RolePathDao;
use \App\com\sprint\sms\api\access\ApiAccess;
use \App\com\sprint\sms\api\access\MenuAccess;
use \App\com\sprint\sms\api\access\RoleAccess;
use \App\com\sprint\sms\api\util\DaoUtil;
use \App\com\sprint\sms\api\util\RequestUtil;
use \App\com\sprint\sms\api\util\AppConstant;

class AccessService
{	
	private $logger;
	
	private $entityManager;
	
	private $cache;
	
	private $settings;
	
	public function __construct(Logger $logger, EntityManager $entityManager, Cache $cache, $settings) {
		$this->logger = $logger;
		$this->entityManager = $entityManager;
		$this->cache = $cache;
		$this->settings = $settings;
	}
	
	/*
	 * GET PUBLIC ACCESS
	 */
	public function getPublicAccess() {
		return $this->cache->get(AppConstant::CACHE_API_LIST, AppConstant::API_KEY_PUBLIC, function($args) {
			return $args[0]->getApiAccess($args[1]);
		}, array($this, AppConstant::API_KEY_PUBLIC));
	}

	
	/*
	 * GET PRIVATE ACCESS
	 */
	public function getPrivateAccess() {
		return $this->cache->get(AppConstant::CACHE_API_LIST, AppConstant::API_KEY_PRIVATE, function($args) {
			$apiPrivate = $args[0]->getApiAccess($args[1]);
			$result = array();
			foreach($apiPrivate as $key => $val) {
				$parent = substr($key, 1, strlen($key));
				$pos = strpos($parent, "/", 0);
				if ($pos) {
					$parent = substr($parent, 0, $pos);
				}
				$parent = "/" . $parent;
				if (!isset($result[$parent])) {
					$result[$parent] = array();
				}
				$map = $result[$parent];
				$map[$key] = $val;
				$result[$parent] = $map;
			}
			return $result;
		}, array($this, AppConstant::API_KEY_PRIVATE));
	}
	
	/*
	 * VALIDATE PATH
	 */
	public function validatePath(Request $request, $path) {
		$apiPublic = $this->getPublicAccess();
		$isPublic = isset($apiPublic[$path]);
		if ($isPublic === false) {
			$key = RequestUtil::getAccessKey($request);
			$key = $key !== null ? trim($key) : "";
			if ($key === "") {
				return Result::ERROR_CODE("90", "Access Key is required");
			}
			$access = $this->getAccess($key);
			if ($access === null) {
				return Result::ERROR_CODE("91", "User Access is not found");
			}
			$userAgent = RequestUtil::getUserAgent($request);
			if ($userAgent !== $access->getAgent()) {
				return Result::ERROR_CODE("92", "User Access is not valid");
			}
			if ($access->hasExpired()) {
				return Result::ERROR_CODE("93", "Access Key has been expired");
			}
			$roleAccess = $this->getRoleAccess($access->getUser()->getRole()->getId());
			if ($roleAccess === null) {
				return Result::ERROR_CODE("94", "Access Role is not found");
			}
			if (!$roleAccess->isAllowAccess($path)) {
				return Result::ERROR_CODE("95", "Access Path is not allowed");
			}
		}
		return null;
	}
	
	
	/*
	 * GET ACCESS
	 */
	public function getAccess($id) {
		$accessDao = $this->getAccessDao();
		return $this->cache->get(AppConstant::CACHE_ACCESS_ID, $id, function($args) {
			$dao = $args[0];
			$aid = $args[1];
			$pAccess = $dao->get($aid);
			if ($pAccess !== null) {
				// Buat Object User baru untuk menghindari error serialize proxy ke cache
				$pUser = $pAccess->getUser();
				$pRole = $pUser->getRole();
				
				$role = new Role();
				$role->setId($pRole->getId());
				$role->setName($pRole->getName());
				$role->setActive($pRole->getActive());
				$role->setVersion($pRole->getVersion());
				$role->setEntryTime($pRole->getEntryTime());
				
				$user = new User();
				$user->setId($pUser->getId());
				$user->setName($pUser->getName());
				$user->setPassword($pUser->getPassword());
				$user->setFirstName($pUser->getFirstName());
				$user->setLastName($pUser->getLastName());
				$user->setEmail($pUser->getEmail());
				$user->setPhone($pUser->getPhone());
				$user->setAvatar($pUser->getAvatar());
				$user->setActive($pUser->getActive());
				$user->setLastLoggedIn($pUser->getLastLoggedIn());
				$user->setLastLoggedOut($pUser->getLastLoggedOut());
				$user->setRole($role);
				$user->setVersion($pUser->getVersion());
				$user->setEntryTime($pUser->getEntryTime());
				
				$access = new Access();
				$access->setId($pAccess->getId());
				$access->setUser($user);
				$access->setAgent($pAccess->getAgent());
				$access->setExpired($pAccess->getExpired());
				$access->setEntryTime($pAccess->getEntryTime());
				return $access;
			}
			return null;
		}, array($accessDao, $id));
	}
	
	
	/*
	 * GET ROLE ACCESS
	 */
	public function getRoleAccess($roleId) {
		$id = $roleId !== null ? $roleId : -1;
		$menuDao = $this->getMenuDao();
		$roleDao = $this->getRoleDao();
		$rolePathDao = $this->getRolePathDao();
		return $this->cache->get(AppConstant::CACHE_ACCESS_ROLE, $id, function($args) {
			$mDao = $args[0];
			$rDao = $args[1];
			$rpDao = $args[2];
			$rid = $args[3];
			$menuList = $mDao->getListByRoleId($rid, true);			
			if ($menuList == null) {
				return null;
			}
			$list = array();
			$map = array();
			for ($i = 0; $i < count($menuList); $i++) {
				$m = $menuList[$i];
				$sm = AccessService::mapper($m);
				$map[$sm->getId()] = AccessService::copy($sm);
				$child1 = $m->getChildren();
				for ($j = 0; $j < count($child1); $j++) {
					$c = $child1[$j];
					$sc = AccessService::mapper($c);
					$sc->setParent(AccessService::mapper($m));
					$child2 = $c->getChildren();
					for ($k = 0; $k < count($child2); $k++) {
						$g = $child2[$k];
						$sg = AccessService::mapper($g);
						$sg->setParent(AccessService::mapper($c));
						$sg->getParent()->setParent(AccessService::mapper($m));
						$scChild = $sc->getChildren();
						array_push($scChild, $sg);
						$sc->setChildren($scChild);
						$map[$sg->getId()] = AccessService::copy($sg);
					}
					$smChild = $sm->getChildren();
					array_push($smChild, $sc);
					$sm->setChildren($smChild);
					$map[$sc->getId()] = AccessService::copy($sc);
				}
				array_push($list, $sm);
			}
			unset($menuList);
			
			$o = new RoleAccess();
			$pathMap = array();
			if ($rid > 0) {
				$role = $rDao->get($rid);
				if ($role != null) {
					$o->setId($role->getId());
					$o->setName($role->getName());
					$rolePathList = $rpDao->findByRoleId($o->getId());
					if ($rolePathList != null) {
						for ($i = 0; $i < count($rolePathList); $i++) {
							$rolePath = $rolePathList[$i];
							$path = $rolePath->getPath();
							$parent = substr($path, 1, strlen($path));
							$pos = strpos($parent, "/", 0);
							if ($pos) {
								$parent = substr($parent, 0, $pos);
							}
							$parent = "/" . $parent;
							if (!isset($pathMap[$parent])) {
								$pathMap[$parent] = array();
							}
							$set = $pathMap[$parent];
							if ($path !== $parent) {
								array_push($set, $path);
								$pathMap[$parent] = $set;
							}
						}					
						unset($rolePathList);
					}
				}
			}
			$o->setPathMap($pathMap);
			$o->setMenuList($list);
			$o->setMenuMap($map);
			return $o;
		}, array($menuDao, $roleDao, $rolePathDao, $id));
	}
	
	
	/*
	 * GET USER
	 */
	public function getUser($name) {
		return $this->getUserDao()->getByName($name);
	}
	
	/*
	 * UPDATE USER
	 */
	public function updateUser(User $user) {
		return $this->getUserDao()->save($user);
	}
	
	/*
	 * REMOVE ACCESS
	 */
	public function removeAccessById($id) {
		return $this->getAccessDao()->delete($id);
	}
	
	/*
	 * REMOVE ACCESS
	 */
	public function removeAccessByUser(User $user) {
		return $this->getAccessDao()->deleteByUser($user);
	}
	
	/*
	 * CREATE ACCESS
	 */
	public function createAccess(User $user, $agent) {
		$access = new Access();
		$access->setAgent($agent);
		$access->setEntryTime(new \DateTime());
		$access->setExpired(round(microtime(true) * 1000) + (AppConstant::ACCESS_EXPIRED * 1000));
		$access->setUser($user);
		return $this->getAccessDao()->save($access);
	}
	
	
	
	
	
	
	
	
	private static function mapper(Menu $menu) {
		$menuAccess = new MenuAccess();
		$action = $menu->getAction();
		if ($action !== null) {
			$count = count($action);
			$menuActions = $menuAccess->getAction();
			if ($menuActions === null) {
				$menuActions = array();
			}
			for ($i = 0; $i < $count; $i++) {
				array_push($menuActions, trim($action[$i]));
			}
			$menuAccess->setAction($menuActions);
		}
		$menuAccess->setDescription($menu->getDescription());
		$menuAccess->setIcon($menu->getIcon());
		$menuAccess->setId($menu->getId());
		$menuAccess->setLink($menu->getLink());
		$menuAccess->setTitle($menu->getTitle());
		return $menuAccess;		
	}
	
	private static function copy(MenuAccess $sm) {
		$m = new MenuAccess();
		$action = $sm->getAction();
		if ($action !== null) {
			$count = count($action);
			$menuActions = $m->getAction();
			if ($menuActions === null) {
				$menuActions = array();
			}
			for ($i = 0; $i < $count; $i++) {
				array_push($menuActions, $action[$i]);			
			}
			$m->setAction($menuActions);
		}
		$m->setDescription($sm->getDescription());
		$m->setIcon($sm->getIcon());
		$m->setId($sm->getId());
		$m->setLink($sm->getLink());
		$m->setTitle($sm->getTitle());
		
		$parent = $sm->getParent();
		if ($parent !== null) {
			$p = new MenuAccess();
			$p->setDescription($parent->getDescription());
			$p->setIcon($parent->getIcon());
			$p->setId($parent->getId());
			$p->setLink($parent->getLink());
			$p->setTitle($parent->getTitle());
			$grandParent = $parent->getParent();
			if ($grandParent !== null) {
				$gp = new MenuAccess();
				$gp->setDescription($grandParent->getDescription());
				$gp->setIcon($grandParent->getIcon());
				$gp->setId($grandParent->getId());
				$gp->setLink($grandParent->getLink());
				$gp->setTitle($grandParent->getTitle());
				$p->setParent($gp);
			}
			$m->setParent($p);
		}
		return $m;
	}
	
	
	private function getApiAccess($group) {
		$content = file_get_contents($this->settings['api']['file']);
		$json = json_decode($content, true);
		$result = array();
		if (!isset($json[$group])) {
			return $result;
		}
		$json = $json[$group];
		$count = count($json);
		for ($i = 0; $i < $count; $i++) {
			$map = $json[$i];
			if (!isset($map[AppConstant::API_KEY_PATH])) {
				continue;
			}
			$path = trim($map[AppConstant::API_KEY_PATH]);
			if (substr($path, 0, 1) !== "/") {
				$path = "/" . $path;
			}
			if (isset($result[$path])) {
				throw new Exception("Duplicate API path: $path");
			}
			$apiAccess = new ApiAccess();
			if (isset($map[AppConstant::API_KEY_DESCRIPTION])) {
				$apiAccess->setDescription($map[AppConstant::API_KEY_DESCRIPTION]);
			}
			if (isset($map[AppConstant::API_KEY_PARAMETER])) {
				$apiParam = array();
				$param = $map[AppConstant::API_KEY_PARAMETER];
				foreach($param as $key => $value) {
					$apiParam[$key] = $value;
				}
				$apiAccess->setParameter($apiParam);
			}
			$result[$path] = $apiAccess->toFormatObject();
		}
		ksort($result);
		return $result;
	}
	
	private function getAccessDao() {
		return new AccessDao($this->entityManager, $this->cache, $this->logger);
	}
	
	private function getUserDao() {
		return new UserDao($this->entityManager, $this->cache, $this->logger);
	}
	
	private function getMenuDao() {
		return new MenuDao($this->entityManager, $this->cache, $this->logger);
	}
	
	private function getRoleDao() {
		return new RoleDao($this->entityManager, $this->cache, $this->logger);
	}
	
	private function getRolePathDao() {
		return new RolePathDao($this->entityManager, $this->cache, $this->logger);
	}
	
}