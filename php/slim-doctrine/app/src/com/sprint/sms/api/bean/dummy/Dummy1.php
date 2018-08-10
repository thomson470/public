<?php
namespace App\com\sprint\sms\api\bean\dummy;

use \App\com\sprint\sms\api\support\FormatSupport;

class Dummy1 implements FormatSupport
{
	private $id;
	private $name;
	private $dummy2;
	
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
	
	public function setDummy2(Dummy2 $dummy2) {
		$this->dummy2 = $dummy2;
	}
	
	public function getDummy2() {
		return $this->dummy2;
	}
	
	public function toFormatObject() {
		$o = array();
		$o['id'] = $this->id;
		$o['name'] = $this->name;
		$o['dummy2'] = isset($this->dummy2) ? $this->dummy2->toFormatObject() : null;		
		return $o;
	}
	
}