<?php
namespace App\com\sprint\sms\api\dao;

use \App\com\sprint\sms\api\base\BaseDao;
use \App\com\sprint\sms\api\bean\Page;
use \App\com\sprint\sms\api\domain\Role;
use \App\com\sprint\sms\api\util\AppConstant;
use \App\com\sprint\sms\api\util\DaoUtil;
use \App\com\sprint\sms\api\util\StringUtil;

class RoleDao extends BaseDao
{
	public function page(Page $page, Role $role, array $orderBy = null, array $visitor = null) {
		$where = "1=1";
		$param = array();
		$alias = "r";
		if ($role->getName() !== null && $role->getName() !== "") {
			$where = $where . " AND " . $alias . ".name LIKE :name";
			$param["name"] = "%" . $role->getName() . "%";
		}
		if ($role->getActive() !== null && $role->getActive() !== "") {
			$where = $where . " AND " . $alias . ".active=:active";
			$param["active"] = $role->getActive();
		}
		$sortBy = array();
		if ($orderBy != null) {
			foreach ($orderBy as $key => $value) {
				$sortBy[$alias . "." . $key] = $value;
			}
		} else {
			$sortBy = array ($alias . "." . Role::ENTRY => "DESC");
		}		
		$page = DaoUtil::query(array(
			DaoUtil::VAR_ENTITY_MANAGER => $this->getEntityManager(),
			DaoUtil::VAR_PAGE => $page,
			DaoUtil::VAR_ENTITY_CLASS => Role::class,
			DaoUtil::VAR_ALIAS => $alias,
			DaoUtil::VAR_WHERE_QUERY => $where,
			DaoUtil::VAR_WHERE_PARAM => $param,
			DaoUtil::VAR_ORDER_BY => $sortBy,
			DaoUtil::VAR_LOG => $this->getLogger()
		));
		return $page;
	}
	
	public function get($id) {
		return DaoUtil::single($this->getEntityManager(), Role::class, $id);
	}

	public function save(Role $role) {
		if ($role->getId() != null) {
			$this->getCache()->remove(AppConstant::CACHE_ACCESS_ROLE, $role->getId());
		}
		return DaoUtil::save($this->getEntityManager(), $role);
	}

	public function delete($id) {
		$this->getCache()->remove(AppConstant::CACHE_ACCESS_ROLE, $id);
		return DaoUtil::deleteById($this->getEntityManager(), Role::class, $id);
	}
	
}