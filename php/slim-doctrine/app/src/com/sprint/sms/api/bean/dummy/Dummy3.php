<?php
namespace App\com\sprint\sms\api\bean\dummy;

use \App\com\sprint\sms\api\support\FormatSupport;

class Dummy3 implements FormatSupport
{
	private $id;
	private $name;
	
	public function setId($id) {
		$this->id = $id;
	}
	
	public function getId() {
		return $this->id;
	}
	
	public function setName($name) {
		$this->name = $name;
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function toFormatObject() {
		$o = array();
		$o['id'] = $this->id;
		$o['name'] = $this->name;		
		return $o;
	}
	
}