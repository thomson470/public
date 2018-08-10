<?php
namespace App\com\sprint\sms\api\dao;

use \App\com\sprint\sms\api\base\BaseDao;
use \App\com\sprint\sms\api\bean\Page;
use \App\com\sprint\sms\api\domain\Role;
use \App\com\sprint\sms\api\domain\User;
use \App\com\sprint\sms\api\domain\Access;
use \App\com\sprint\sms\api\util\AppConstant;
use \App\com\sprint\sms\api\util\DaoUtil;

class AccessDao extends BaseDao
{
	public function get($id) {
		return DaoUtil::single($this->getEntityManager(), Access::class, $id);
	}
	
	public function getByUserId($userId) {
		$user = new User();
		$user->setId($userId);
		return DaoUtil::unique($this->getEntityManager(), Access::class, array("user" => $user));
	}
	
	public function save($access) {
		if ($access->getId() !== null) {
			$this->getCache()->remove(AppConstant::CACHE_ACCESS_ID, $access->getId());
		}
		return DaoUtil::save($this->getEntityManager(), $access);
	}

	public function delete($id) {
		$this->getCache()->remove(AppConstant::CACHE_ACCESS_ID, $id);
		return DaoUtil::deleteById($this->getEntityManager(), Access::class, $id);
	}
	
	public function deleteByUser(User $user) {
		$access = DaoUtil::unique($this->getEntityManager(), Access::class, array("user" => $user));
		if ($access !== null) {
			$this->getCache()->remove(AppConstant::CACHE_ACCESS_ID, $access->getId());
			return DaoUtil::delete($this->getEntityManager(), $access);
		}
		return null;
	}
	
}