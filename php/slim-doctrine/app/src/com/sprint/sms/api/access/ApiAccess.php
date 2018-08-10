<?php
namespace App\com\sprint\sms\api\access;

use \App\com\sprint\sms\api\support\FormatSupport;
use \App\com\sprint\sms\api\util\AppConstant;

class ApiAccess implements FormatSupport
{
	private $description;
	
	private $parameter = array();
	
	public function getDescription() {
		return $this->description;
	}

	public function setDescription($description) {
		$this->description = $description;
	}

	public function getParameter() {
		return $this->parameter;
	}

	public function setParameter(array $parameter) {
		$this->parameter = $parameter;
	}
	
	public function toFormatObject() {
		$o = array();
		$o[AppConstant::API_KEY_DESCRIPTION] = $this->description;	
		if ($this->parameter !== null && count($this->parameter) !== 0) {
			$o[AppConstant::API_KEY_PARAMETER] = $this->parameter;
		}
		return $o;
	}
	
}