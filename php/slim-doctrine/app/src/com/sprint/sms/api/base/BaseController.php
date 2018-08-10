<?php
namespace App\com\sprint\sms\api\base;

use \Monolog\Logger as Logger;
use \Doctrine\ORM\EntityManager as EntityManager;

abstract class BaseController
{
	private $logger;

	private $entityManager;
	
	private $cache;
	
	private $settings;
	
	private $request;
	
	public function __construct($app, $request) {
		$container = $app->getContainer();	
		$this->logger = $container->get('logger');
		$this->entityManager = $container->get('em');
		$this->cache = $container->get('cache');
		$this->settings = $container["settings"];
		$this->request = $request;
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
	
	protected function getSettings() {
		return $this->settings;
	}
	
	protected function getRequest() {
		return $this->request;
	}
	
}