<?php
namespace App\com\sprint\sms\api\base\domain;

use \Doctrine\ORM\Mapping as ORM;

use \App\com\sprint\sms\api\support\FormatSupport;

/**
 * @ORM\MappedSuperclass
 */
abstract class BaseEntry implements FormatSupport
{
	const ENTRY = "entryTime";
	
	/**
     * @ORM\Column(name = "ENTRY_", type = "datetime", nullable = false)
     */
	private $entryTime;
	
	public function setEntryTime($entryTime) {
		$this->entryTime = $entryTime;
	}
	
	public function getEntryTime() {
		return $this->entryTime;
	}
	
	public function toFormatObject() {
		$o = array();
		$o[self::ENTRY] = isset($this->entryTime) ? $this->entryTime->getTimestamp() * 1000 : null;
		return $o;
	}
	
}
