<?php
namespace App\com\sprint\sms\api\domain;

use Doctrine\ORM\Mapping as ORM;

use \App\com\sprint\sms\api\base\domain\BaseEntry;
use \App\com\sprint\sms\api\support\FormatSupport;

use \App\com\sprint\sms\api\domain\Role;

/**
 * @ORM\Entity
 * @ORM\Table(name = "t_role_path", uniqueConstraints = {@ORM\UniqueConstraint(name = "role_path_idx", columns = {"f_role", "path"})}) 
 */
class RolePath extends BaseEntry implements FormatSupport
{

	/**
     * @ORM\Column(name="ID_", type = "string")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy = "UUID")
     */
	private $id;
	
	/**
	 * @ORM\ManyToOne(targetEntity = "Role", fetch = "EAGER")
	 * @ORM\JoinColumn(name = "f_role", referencedColumnName = "ID_", nullable = false, onDelete="CASCADE")
	 */ 
    private $role;
	
	/**
     * @ORM\Column(name = "path", type = "string", nullable = false)
     */
    private $path;
	
	
	public function setId($id) {
		$this->id = $id;
	}
	
	public function getId() {
		return $this->id;
	}	
	
	public function setRole(Role $role)
    {
        $this->role = $role;
    }
	
    public function getRole()
    {
        return $this->role;
    }
	
	public function setPath($path) {
		$this->path = $path;
	}
	
	public function getPath() {
		return $this->path;
	}
	
	public function toFormatObject() {
		$o = parent::toFormatObject();
		$o["id"] = $this->id;
		$o["role"] = isset($this->role) ? array("id" => $this->role->getId()) : null;
		$o["path"] = $this->path;
		return $o;
	}
	
}