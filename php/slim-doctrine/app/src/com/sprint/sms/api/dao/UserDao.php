<?php
namespace App\com\sprint\sms\api\dao;

use \App\com\sprint\sms\api\base\BaseDao;
use \App\com\sprint\sms\api\bean\Page;
use \App\com\sprint\sms\api\domain\User;
use \App\com\sprint\sms\api\util\AppConstant;
use \App\com\sprint\sms\api\util\DaoUtil;

class UserDao extends BaseDao
{
	public function page(Page $page, User $user, array $orderBy = null, array $visitor = null) {
		$where = "1=1";
		$param = array();
		$alias = "u";
		if ($user->getName() !== null && $user->getName() !== "") {
			$where = $where . " AND (" . $alias . ".name LIKE :name OR " . $alias . ".firstName LIKE :name)";
			$param["name"] = "%" . $user->getName() . "%";
		}
		if ($user->getEmail() !== null && $user->getEmail() !== "") {
			$where = $where . " AND " . $alias . ".email LIKE :email";
			$param["email"] = "%" . $user->getEmail() . "%";
		}
		if ($user->getActive() !== null && $user->getActive() !== "") {
			$where = $where . " AND " . $alias . ".active=:active";
			$param["active"] = $user->getActive();
		}
		$sortBy = array();
		if ($orderBy != null) {
			foreach ($orderBy as $key => $value) {
				$sortBy[$alias . "." . $key] = $value;
			}
		} else {
			$sortBy = array ($alias . "." . User::ENTRY => "DESC");
		}		
		$page = DaoUtil::query(array(
			DaoUtil::VAR_ENTITY_MANAGER => $this->getEntityManager(),
			DaoUtil::VAR_PAGE => $page,
			DaoUtil::VAR_ENTITY_CLASS => User::class,
			DaoUtil::VAR_ALIAS => $alias,
			DaoUtil::VAR_WHERE_QUERY => $where,
			DaoUtil::VAR_WHERE_PARAM => $param,
			DaoUtil::VAR_ORDER_BY => $sortBy,
			DaoUtil::VAR_LOG => $this->getLogger()
		));		
		return $page;
	}
	
	public function get($id) {
		return DaoUtil::single($this->getEntityManager(), User::class, $id);
	}

	public function save(User $user) {
		return DaoUtil::save($this->getEntityManager(), $user);
	}

	public function delete($id) {
		return DaoUtil::deleteById($this->getEntityManager(), User::class, $id);
	}
	
	public function getByName($name) {
		return DaoUtil::unique($this->getEntityManager(), User::class, array("name" => $name));
	}
	
}