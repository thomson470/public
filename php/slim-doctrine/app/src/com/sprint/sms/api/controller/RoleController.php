<?php
namespace App\com\sprint\sms\api\controller;

use \App\com\sprint\sms\api\bean\Response;
use \App\com\sprint\sms\api\bean\Page;
use \App\com\sprint\sms\api\util\RequestUtil;
use \App\com\sprint\sms\api\util\ObjectUtil;
use \App\com\sprint\sms\api\cache\Constant;
use \App\com\sprint\sms\api\domain\Role;
use \App\com\sprint\sms\api\domain\RolePath;
use \App\com\sprint\sms\api\domain\RoleMenu;
use \App\com\sprint\sms\api\dao\MenuDao;
use \App\com\sprint\sms\api\dao\RoleDao;
use \App\com\sprint\sms\api\dao\RolePathDao;
use \App\com\sprint\sms\api\dao\RoleMenuDao;
use \App\com\sprint\sms\api\service\AccessService;
use \App\com\sprint\sms\api\base\BaseController;

class RoleController extends BaseController
{

	public function search() {
		$request = $this->getRequest();
		$page = RequestUtil::paramsToPage($request, Role::class);
		$role = RequestUtil::paramsToObject($request, Role::class);
		$order 	= RequestUtil::paramsToOrder($request, Role::class);
		$result = $this->getRoleDao()->page($page, $role, $order);
		return Response::SUCCESS($result);
	}
	
	public function view() {
		$request = $this->getRequest();
		$id = RequestUtil::paramsToId($request);
		if (!isset($id)) {
			return Response::ERROR_CODE("01", "id is required");
		}
		$entity = $this->getRoleDao()->get($id);
		return Response::SUCCESS($entity);
	}
	
	public function create() {
		$request = $this->getRequest();
		$entity = RequestUtil::paramsToObject($request, Role::class);
		$entity = $this->getRoleDao()->save($entity);
		return Response::SUCCESS($entity); 
	}
	
	public function update() {
		$request = $this->getRequest();
		$new = RequestUtil::paramsToObject($request, Role::class);
		$id = $new->getId();
		if (!isset($id)) {
			return Response::ERROR_CODE("01", "id is required");
		}
		$old = $this->getRoleDao()->get($id);
		if ($old == null) {
			return Response::ERROR_CODE("02", "Object is not found");
		}
		$ignoredField = ObjectUtil::getDefaultIgnoredField();
		ObjectUtil::copy($old, $new, $ignoredField, array(
			"name" => array(ObjectUtil::NOT_NULL, ObjectUtil::NOT_EMPTY),
			"active" => array(ObjectUtil::NOT_NULL)
		));
		$entity = $this->getRoleDao()->save($old);		
		return Response::SUCCESS($entity);
	}
	
	public function delete() {
		$request = $this->getRequest();
		$id = RequestUtil::paramsToId($request);
		if (!isset($id)) {
			return Response::ERROR_CODE("01", "id is required");
		}
		$entity = $this->getRoleDao()->delete($id);
		return Response::SUCCESS($entity);
	}



	
	public function path__list() {
		$request = $this->getRequest();
		$entity = RequestUtil::paramsToObject($request, RolePath::class);
		$roleId = $entity != null && $entity->getRole() != null ? $entity->getRole()->getId() : null;
		if ($roleId == null) {
			return Response::ERROR_CODE("01", "Role Id is required");
		}
		$path = $entity != null && $entity->getPath() != null ? trim($entity->getPath()) : "";
		$result;
		$rolePathDao = $this->getRolePathDao();
		if (strlen($path) != 0) {
			$result = $rolePathDao->findByRoleIdAndPathGroup($roleId, $path . "%");
		} else {
			$result = $rolePathDao->findByRoleId($roleId);
		}		
		return Response::SUCCESS($result);
	}
	
	public function path__save() {
		$request = $this->getRequest();
		$entity = RequestUtil::paramsToObject($request, RolePath::class);
		$roleId = $entity != null && $entity->getRole() != null ? $entity->getRole()->getId() : null;
		if ($roleId == null) {
			return Response::ERROR_CODE("01", "Role Id is required");
		}
		$path = $entity != null ? $entity->getPath() : null;
		$path = $path != null ? trim($path) : "";
		if (strlen($path) == 0) {
			return Response::ERROR_CODE("02", "path is required");
		}
		$rolePathDao = $this->getRolePathDao();
		$rolePath = $rolePathDao->getByRoleIdAndPath($roleId, $path);
		if ($rolePath != null) {
			return Response::SUCCESS($rolePath);
		}
		$roleDao = $this->getRoleDao();
		$role = $roleDao->get($roleId);
		if ($role == null) {
			return Response::ERROR_CODE("03", "Role is not found");
		}
		$rolePath = new RolePath();
		$rolePath->setPath($path);
		$rolePath->setRole($role);
		$entity = $rolePathDao->save($rolePath);
		return Response::SUCCESS($entity);
	}
	
	public function path__delete() {
		$request = $this->getRequest();
		$id = RequestUtil::paramsToId($request);
		if ($id == null) {
			return Response::ERROR_CODE("01", "id is required");
		}
		$rolePathDao = $this->getRolePathDao();
		$entity = $rolePathDao->delete($id);
		return Response::SUCCESS($entity);
	}
	
	public function path__trash() {
		$request = $this->getRequest();
		$entity = RequestUtil::paramsToObject($request, RolePath::class);
		$role = $entity != null ? $entity->getRole() : null;
		if ($role != null) {
			$roleId = $role != null ? $role->getId() : null;
			if ($roleId == null) {
				return Response::ERROR_CODE("01", "Role Id is required");
			}
			$roleDao = $this->getRoleDao();
			$role = $roleDao->get($roleId);
			if ($role == null) {
				return Response::ERROR_CODE("02", "Role is not found");
			}
		}
		$rolePathDao = $this->getRolePathDao();
		$accessService = $this->getAccessService();
		$result = array();
		$apiPrivate = $accessService->getPrivateAccess();
		foreach($apiPrivate as $group => $value) {
			$paths = array_keys($apiPrivate[$group]);
			$count = $rolePathDao->deleteByRoleAndGroupAndPathList($role, $group, $paths);
			$result[$group] = $count;
		}
		$count = $rolePathDao->deleteByRoleAndGroupList($role, array_keys($apiPrivate));
		$result["UNKNOWN"] = $count;
		return Response::SUCCESS($result);
	}
	
	
	
	
	public function menu__list() {
		$request = $this->getRequest();
		$entity = RequestUtil::paramsToObject($request, RoleMenu::class);
		$roleId = $entity != null && $entity->getRole() != null ? $entity->getRole()->getId() : null;
		if ($roleId == null) {
			return Response::ERROR_CODE("01", "Role Id is required");
		}
		$roleMenuDao = $this->getRoleMenuDao();
		$result = $roleMenuDao->findByRoleId($roleId);
		return Response::SUCCESS($result);
	}
	
	public function menu__save() {
		$request = $this->getRequest();
		$entity = RequestUtil::paramsToObject($request, RoleMenu::class);
		$roleId = $entity != null && $entity->getRole() != null ? $entity->getRole()->getId() : null;
		if ($roleId == null) {
			return Response::ERROR_CODE("01", "Role Id is required");
		}
		$menuId = $entity != null && $entity->getMenu() != null ? $entity->getMenu()->getId() : null;
		if ($menuId == null) {
			return Response::ERROR_CODE("02", "Menu Id is required");
		}
		$roleMenuDao = $this->getRoleMenuDao();
		$roleMenu = $roleMenuDao->getByRoleIdAndMenuId($roleId, $menuId);
		if ($roleMenu != null) {
			return Response::SUCCESS($roleMenu);
		}
		$roleDao = $this->getRoleDao();
		$role = $roleDao->get($roleId);
		if ($role == null) {
			return Response::ERROR_CODE("03", "Role is not found");
		}
		$menuDao = $this->getMenuDao();
		$menu = $menuDao->get($menuId);
		if ($menu == null) {
			return Response::ERROR_CODE("04", "Menu is not found");
		}
		$action = $entity->getAction();
		$action = $action != null ? str_replace(" ", "", trim($action)) : "";
		$roleMenu = new RoleMenu();
		$roleMenu->setRole($role);
		$roleMenu->setMenu($menu);
		$roleMenu->setAction($action);
		$entity = $roleMenuDao->save($roleMenu);
		return Response::SUCCESS($entity);
	}
	
	public function menu__delete() {
		$request = $this->getRequest();
		$id = RequestUtil::paramsToId($request);
		if ($id == null) {
			return Response::ERROR_CODE("01", "id is required");
		}
		$roleMenuDao = $this->getRoleMenuDao();
		$entity = $roleMenuDao->delete($id);
		return Response::SUCCESS($entity);
	}
	
	
	
	
	private function getRoleDao() {
		//return $this->getCache()->get(Constant::__APP_DAO, "DAO_ROLE", function() {
			return new RoleDao($this->getEntityManager(), $this->getCache(), $this->getLogger());
		//});
	}	
	
	private function getMenuDao() {
		return new MenuDao($this->getEntityManager(), $this->getCache(), $this->getLogger());
	}
	
	private function getRolePathDao() {
		return new RolePathDao($this->getEntityManager(), $this->getCache(), $this->getLogger());
	}
	
	private function getRoleMenuDao() {
		return new RoleMenuDao($this->getEntityManager(), $this->getCache(), $this->getLogger());
	}
	
	private function getAccessService() {
		return new AccessService($this->getLogger(), $this->getEntityManager(), $this->getCache(), $this->getSettings());
	}
	
}