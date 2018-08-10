<?php
namespace App\com\sprint\sms\api\bean;

use \App\com\sprint\sms\api\support\FormatSupport;

class Response implements FormatSupport
{
	const SUCCESS 		= "SUCCESS";
	const INPROGRESS 	= "INPROGRESS";
	const FAILED 		= "FAILED";
	const ERROR			= "ERROR";	

	private $status;
	
	private $error; // List of CodeMsg
	
	private $data;  // Object extends \App\Base\Object
	
	private $info;	
	
	public function getStatus()
    {
        return $this->status;
    }
	
	public function setStatus($status)
    {
        $this->status = $status;
    }
	
	public function getError()
    {
        return $this->error;
    }
	
	public function setError($error)
    {
        $this->error = $error;
    }
	
	public function getData()
    {
        return $this->data;
    }
	
	public function setData($data)
    {
        $this->data = $data;
    }
	
	public function getInfo()
    {
        return $this->info;
    }
	
	public function setInfo($info)
    {
        $this->info = $info;
    }
	
	public function toFormatObject() {
		$o = array();
		$o["status"] = $this->status;
		$err = $this->error;
		if (isset($err) && is_array($err)) {
			$o["error"] = array();
			for ($i = 0; $i < count($err); $i++) {				
				$o["error"][$i] = $err[$i]->toFormatObject();
			}
		}
		$data = $this->data;
		if (isset($data)) {
			if (is_array($data)) {
				$count = count($data);
				if ($count > 0) {
					if (isset($data[0]) && $data[0] instanceof FormatSupport) {
						$o["data"] = array();
						for ($i = 0; $i < count($data); $i++) {				
							$o["data"][$i] = $data[$i]->toFormatObject();
						}
					} else {
						$o["data"] = $data;
					}
				} else {
					$o["data"] = $data;
				}
			} else if ($data instanceof FormatSupport) {
				$o["data"] = $data->toFormatObject();
			} else {
				$o["data"] = $data;
			}
		}
		if (isset($this->info)) {
			$o["info"] = $this->info;
		}
		return $o;
	}
	
	
	/*
	 * STATIC
	 */	 
	public static function STATUS($status, $data = null, $error = null) {
		$r = new self();
		$r->setStatus($status);
		$r->setData($data);
		$r->setError($error);
		return $r;
	}
	 
	public static function SUCCESS($data = null) {
		return self::STATUS(self::SUCCESS, $data, null);
	}
	
	public static function ERROR($error = null) {
		return self::STATUS(self::ERROR, null, $error);
	}
	
	public static function ERROR_CODE($code, $message = null) {
		$msg = new CodeMsg();
		$msg->setCode($code);
		$msg->setMessage($message);
		return self::STATUS(self::ERROR, null, array($msg));
	}
}
