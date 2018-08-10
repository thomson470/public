<?php
namespace App\com\sprint\sms\api\bean\dummy;

use \App\com\sprint\sms\api\support\FormatSupport;

class Dummy2 implements FormatSupport
{
	private $id;
	private $name;
	private $dummy3;
	
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
	
	public function setDummy3(Dummy3 $dummy3) {
		$this->dummy3 = $dummy3;
	}
	
	public function getDummy3() {
		return $this->dummy3;
	}
	
	public function toFormatObject() {
		$o = array();
		$o['id'] = $this->id;
		$o['name'] = $this->name;
		$o['dummy3'] = isset($this->dummy3) ? $this->dummy3->toFormatObject() : null;		
		return $o;
	}
	
}