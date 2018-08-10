<?php
namespace App\com\sprint\sms\api\dao;

use \App\com\sprint\sms\api\base\BaseDao;
use \App\com\sprint\sms\api\bean\Page;
use \App\com\sprint\sms\api\domain\Role;
use \App\com\sprint\sms\api\domain\RolePath;
use \App\com\sprint\sms\api\util\AppConstant;
use \App\com\sprint\sms\api\util\DaoUtil;
use \App\com\sprint\sms\api\util\StringUtil;

class RolePathDao extends BaseDao
{
	public function findByRoleId($roleId) {
		$role = new Role();
		$role->setId($roleId);		
		return DaoUtil::search(array(
			DaoUtil::VAR_ENTITY_MANAGER => $this->getEntityManager(),
			DaoUtil::VAR_ENTITY_CLASS => RolePath::class,
			DaoUtil::VAR_CRITERIA => array("role" => $role),
			DaoUtil::VAR_ORDER_BY => array("path" => "ASC")
		));
	}
	
	public function findByRoleIdAndPathGroup($roleId, $group) {
		$role = new Role();
		$role->setId($roleId);
		$alias = "o";
		$sortBy = array ($alias . ".path" => "ASC");
		if (!StringUtil::endsWith($group, "%")) {
			$group = $group . "%";
		}
		return DaoUtil::query(array(
			DaoUtil::VAR_ENTITY_MANAGER => $this->getEntityManager(),
			DaoUtil::VAR_ENTITY_CLASS => RolePath::class,
			DaoUtil::VAR_ALIAS => $alias,
			DaoUtil::VAR_WHERE_QUERY => "o.role = :role AND o.path LIKE :group",
			DaoUtil::VAR_WHERE_PARAM => array("role" => $role, "group" => $group),
			DaoUtil::VAR_ORDER_BY => $sortBy,
			DaoUtil::VAR_LOG => $this->getLogger()
		));
	}
	
	public function getByRoleIdAndPath($roleId, $path) {
		$role = new Role();
		$role->setId($roleId);
		return DaoUtil::unique(
			$this->getEntityManager(), 
			RolePath::class, 
			array("role" => $role, "path" => $path)
		);
	}
	
	public function save(RolePath $rolePath) {
		$role = $rolePath->getRole();
		if ($role !== null) {
			$this->getCache()->remove(AppConstant::CACHE_ACCESS_ROLE, $role->getId());
		}
		return DaoUtil::save($this->getEntityManager(), $rolePath);
	}

	public function delete($id) {
		$rolePath = DaoUtil::deleteById($this->getEntityManager(), RolePath::class, $id);
		if ($rolePath !== null && $rolePath->getRole() !== null) {
			$this->getCache()->remove(AppConstant::CACHE_ACCESS_ROLE, $rolePath->getRole()->getId());
		}
		return $rolePath;
	}
	
	public function deleteByRoleAndGroupAndPathList(Role $role = null, $group, array $paths) {
		if (!StringUtil::endsWith($group, "%")) {
			$group = $group . "%";
		}
		$entityClass = RolePath::class;
		$dql = "DELETE FROM $entityClass AS o WHERE o.path LIKE :group AND o.path NOT IN (:paths)";
		if ($role !== null) {
			$dql = $dql . " AND o.role = :role";
		}
		$query = $this->getEntityManager()->createQuery($dql);
		$query->setParameter("group", $group);
		$query->setParameter("paths", $paths, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
		if ($role !== null) {
			$query->setParameter("role", $role);
		}
		$result = $query->execute();
		return $result;
	}
	
	public function deleteByRoleAndGroupList(Role $role = null, array $groups) {
		$entityClass = RolePath::class;
		$dql = "SELECT u.id FROM $entityClass AS u WHERE u.id NOT IN (SELECT o.id FROM $entityClass AS o WHERE";
		$size = count($groups);
		for ($i = 0; $i < $size; $i++) {
			$dql = $dql . ($i === 0 ? " " : " OR ") . "o.path LIKE :path" . $i;
		}
		$dql = $dql . ")";
		if ($role !== null) {
			$dql = $dql . " AND u.role = :role";
		}
		$query = $this->getEntityManager()->createQuery($dql);
		for ($i = 0; $i < $size; $i++) {
			$query->setParameter("path" . $i, $groups[$i] . "%");
		}
		if ($role != null) {
			$query->setParameter("role", $role);
		}
		$ids = $query->getResult();
		$result = 0;
		if ($ids !== null && count($ids) !== 0) {
			$dql = "DELETE FROM $entityClass AS u WHERE u.id IN (:ids)";
			$query = $this->getEntityManager()->createQuery($dql);
			$query->setParameter("ids", $ids, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
			$result = $query->execute();
		}
		return $result;
	}
	
}