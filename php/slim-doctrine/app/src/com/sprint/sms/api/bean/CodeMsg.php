<?php
namespace App\com\sprint\sms\api\bean;

use \App\com\sprint\sms\api\support\FormatSupport;

class CodeMsg implements FormatSupport
{

	private $code;
	
	private $object;
	
	private $field;
	
	private $message;
	
	
	public function getCode()
    {
        return $this->code;
    }
	
	public function setCode($code)
    {
        $this->code = $code;
    }
	
	public function getObject()
    {
        return $this->object;
    }
	
	public function setObject($object)
    {
        $this->object = $object;
    }
	
	public function getField()
    {
        return $this->field;
    }
	
	public function setField($field)
    {
        $this->field = $field;
    }
	
	public function getMessage()
    {
        return $this->message;
    }
	
	public function setMessage($message)
    {
        $this->message = $message;
    }
	
	public function toFormatObject() {
		$o = array();
		if (isset($this->code)) {
			$o["code"] = $this->code;
		}
		if (isset($this->object)) {
			$o["object"] = $this->object;
		}
		if (isset($this->field)) {
			$o["field"] = $this->field;
		}
		if (isset($this->message)) {
			$o["message"] = $this->message;
		}
		return $o;
	}
	
}
	