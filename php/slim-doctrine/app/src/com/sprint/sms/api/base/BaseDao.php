<?php
namespace App\com\sprint\sms\api\base;

use \Monolog\Logger;
use \Doctrine\ORM\EntityManager;
use \App\com\sprint\sms\api\cache\Cache;

abstract class BaseDao
{
	private $logger;

	private $entityManager;
	
	private $cache;
	
	public function __construct(EntityManager $entityManager, Cache $cache, Logger $logger) {
		$this->entityManager = $entityManager;
		$this->cache = $cache;
		$this->logger = $logger;
	}
	
	protected function getLogger() {
		return $this->logger;
	}
	
	protected function getEntityManager() {
		return $this->entityManager;
	}
	
	protected function getCache() {
		return $this->cache;
	}
	
}