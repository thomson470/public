<?php
namespace App\com\sprint\sms\api\domain;

use Doctrine\ORM\Mapping as ORM;
use \App\com\sprint\sms\api\base\domain\BaseEntryVersion;
use \App\com\sprint\sms\api\support\FormatSupport;

/**
 * @ORM\Entity
 * @ORM\Table(name = "t_role")
 */
class Role extends BaseEntryVersion implements FormatSupport
{

	/**
     * @ORM\Column(name = "ID_", type = "bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy = "IDENTITY")
	 */
	private $id;
	
	/**
     * @ORM\Column(name = "name", type = "string", length = 150, nullable = false)
     */
    private $name;
	
	/**
     * @ORM\Column(name = "active", type = "boolean", nullable = false, options = {"default":true})
     */
    private $active;
	
	
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
	
	public function setActive($active) {
		$this->active = $active;
	}
	
	public function getActive() {
		return $this->active;
	}
	
	
	public function toFormatObject() {
		$o = parent::toFormatObject();
		$o["id"] = $this->id;
		$o["name"] = $this->name;
		$o["active"] = isset($this->active) ? ($this->active ? 1 : 0) : null;	
		return $o;
	}
	
}