<?php
namespace App\com\sprint\sms\api\service;

use \Monolog\Logger;
use \Doctrine\ORM\EntityManager;
use \App\com\sprint\sms\api\support\FormatSupport;
use \App\com\sprint\sms\api\domain\Audit;
use \App\com\sprint\sms\api\util\DaoUtil;

/*
 * Untuk yang asynchrounus butuh library tambahan PECL
 * Saat ini kita pakai synchrounus dulu :D
 */
class AuditService /*extends Thread*/
{	
	private $entityManager;
	
	private $logger;

	private $auditList;
	
	public function __construct(Logger $logger, EntityManager $entityManager, array $auditList = null) {
		$this->logger = $logger;
		$this->entityManager = $entityManager;
		$this->auditList = $auditList;
	}
	
	public function run() {
		if ($this->auditList === null) {
			return;
		}
		$count = count($this->auditList);
		for ($i = 0; $i < $count; $i++) {
			//$content = $this->auditList[$i] instanceof FormatSupport ? $this->auditList[$i]->toFormatObject() : $this->auditList[$i];
			//$content = json_encode($content);
			//$audit = new Audit();
			//$audit->
			DaoUtil::save($this->entityManager, $this->auditList[$i]);
		}
	}
	
	public function start() {
		$this->run();
	}
	
}