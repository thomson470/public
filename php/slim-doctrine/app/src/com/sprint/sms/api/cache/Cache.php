<?php
namespace App\com\sprint\sms\api\cache;

use Exception;
use ReflectionClass;

abstract class Cache
{
	protected $config;
	
	public function __construct(array $config) {
		$this->config = $config;
	}
	
	public function get($group, $key, callable $callback = null, array $args = null) {		
		if (!isset($this->config[$group])) {
			throw new Exception("Cache group is not registered: $group");
		}
		$value = $this->doGet($group, $key);
		if ($value != null) {
			if ($value instanceof NullObject) {
				return null;
			}
			return $value;
		}
		if ($callback != null) {
			$value = $callback($args);
			if ($value != null) {
				$this->doPut($group, $key, $value);
			} else {
				if ($this->config[$group]['allowNull'] == 1) {
					$this->doPut($group, $key, new NullObject());
				}
			}
		}
		return $value;		
	}
	
	public function remove($group, $key) {
		if (!isset($this->config[$group])) {
			return;
		}
		return $this->doRemove($group, $key);
	}
	
	public function clear($group) {
		if (!isset($this->config[$group])) {
			return;
		}
		return $this->doClear($group);
	}
	
	
	abstract protected function doGet($group, $key);
	
	abstract protected function doPut($group, $key, $value);
	
	abstract protected function doRemove($group, $key);
	
	abstract protected function doClear($group);
	
	
	// Untuk membuat instance baru, tergantung dari class provider yang digunakan
	// Lihat di settings.php di bagian 'cache'.
	public static function create(array $config) 
	{
		$provider = $config["provider"];
		$cls = new ReflectionClass($provider);
		return $cls->newInstanceArgs(array($config["group"]));
	}
	
}