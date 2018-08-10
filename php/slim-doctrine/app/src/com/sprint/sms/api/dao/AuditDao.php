<?php
namespace App\com\sprint\sms\api\dao;

use \App\com\sprint\sms\api\base\BaseDao;
use \App\com\sprint\sms\api\bean\Page;
use \App\com\sprint\sms\api\domain\Audit;
use \App\com\sprint\sms\api\cache\Constant;
use \App\com\sprint\sms\api\util\DaoUtil;

class AuditDao extends BaseDao
{
	public function save(Audit $audit) {
		return DaoUtil::save($this->getEntityManager(), $audit);
	}
	
}