<?php
namespace App\com\sprint\sms\api\dao;

use \App\com\sprint\sms\api\base\BaseDao;
use \App\com\sprint\sms\api\bean\Page;
use \App\com\sprint\sms\api\domain\Role;
use \App\com\sprint\sms\api\domain\Menu;
use \App\com\sprint\sms\api\domain\RoleMenu;
use \App\com\sprint\sms\api\util\AppConstant;
use \App\com\sprint\sms\api\util\DaoUtil;

class RoleMenuDao extends BaseDao
{

	public function findByRoleId($roleId) {
		$role = new Role();
		$role->setId($roleId);		
		return DaoUtil::search(array(
			DaoUtil::VAR_ENTITY_MANAGER => $this->getEntityManager(),
			DaoUtil::VAR_ENTITY_CLASS => RoleMenu::class,
			DaoUtil::VAR_CRITERIA => array("role" => $role)
		));
	}
	
	public function getByRoleIdAndMenuId($roleId, $menuId) {
		$role = new Role();
		$role->setId($roleId);
		$menu = new Menu();
		$menu->setId($menuId);
		return DaoUtil::unique(
			$this->getEntityManager(), 
			RoleMenu::class, 
			array("role" => $role, "menu" => $menu)
		);
	}
	
	public function save($menuRole) {
		$role = $menuRole->getRole();
		if ($role !== null) {
			$this->getCache()->remove(AppConstant::CACHE_ACCESS_ROLE, $role->getId());
		}
		return DaoUtil::save($this->getEntityManager(), $menuRole);
	}

	public function delete($id) {
		$roleMenu = DaoUtil::deleteById($this->getEntityManager(), RoleMenu::class, $id);
		if ($roleMenu !== null && $roleMenu->getRole() !== null) {
			$this->getCache()->remove(AppConstant::CACHE_ACCESS_ROLE, $roleMenu->getRole()->getId());
		}
		return $roleMenu;
	}
	
}