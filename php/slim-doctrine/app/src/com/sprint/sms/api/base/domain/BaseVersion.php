<?php
namespace App\com\sprint\sms\api\base\domain;

use \Doctrine\ORM\Mapping as ORM;

use \App\com\sprint\sms\api\support\FormatSupport;

/**
 * @ORM\MappedSuperclass
 */
abstract class BaseVersion implements FormatSupport
{
	const VERSION 	= "version";
	
	/**
     * @ORM\Column(name = "VERSION_", type = "bigint", nullable = false)
	 * @ORM\Version
     */
	private $version;
	
	public function setVersion($version) {
		$this->version = $version;
	}
	
	public function getVersion() {
		return $this->version;
	}
	
	public function toFormatObject() {
		$o = array();
		$o[self::VERSION] = $this->version;
		return $o;
	}
	
}