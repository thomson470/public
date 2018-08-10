<?php
namespace App\com\sprint\sms\api\cache;

class Store
{
	private $value;
	
	private $lifetime;
	
	public function __construct($value, $age) {
		$this->value = $value;
		if ($age > 0) {
			$this->lifetime = round(microtime(true) * 1000) + $age;
		} else {
			$this->lifetime = 0;
		}
	}
	
	public function getValue() {
		return $this->value;
	}
	
	public function isExpired() {
		if ($this->lifetime == 0) {
			return false;
		}
		return round(microtime(true) * 1000) > $this->lifetime;
	}
}