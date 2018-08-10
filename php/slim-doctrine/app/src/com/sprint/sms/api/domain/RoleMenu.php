<?php
namespace App\com\sprint\sms\api\domain;

use Doctrine\ORM\Mapping as ORM;

use \App\com\sprint\sms\api\domain\Role;
use \App\com\sprint\sms\api\domain\Menu;
use \App\com\sprint\sms\api\support\FormatSupport;

/**
 * @ORM\Entity
 * @ORM\Table(name = "t_role_menu", uniqueConstraints = {@ORM\UniqueConstraint(name = "role_menu_idx", columns = {"f_role", "f_menu"})}) 
 */
class RoleMenu implements FormatSupport
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
	 * @ORM\ManyToOne(targetEntity = "Menu", fetch = "EAGER")
	 * @ORM\JoinColumn(name = "f_menu", referencedColumnName = "ID_", nullable = false, onDelete="CASCADE")
	 */ 
    private $menu;
	
	/**
     * @ORM\Column(name = "action", type = "string", length = 150, nullable = false)
     */
    private $action;
	
	
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
	
	public function setMenu(Menu $menu)
    {
        $this->menu = $menu;
    }
	
    public function getMenu()
    {
        return $this->menu;
    }
	
	public function setAction($action) {
		$this->action = $action;
	}
	
	public function getAction() {
		return $this->action;
	}
	
	
	
	private $actionAsSet;
	
	public function getActionAsSet() {
		if ($this->actionAsSet != null) {
			return $this->actionAsSet;
		}
		$this->actionAsSet = array();
		if ($this->action === null) {
			return $this->actionAsSet;
		}
		$split = explode(",", $this->action);
		$count = count($split);
		for ($i = 0; $i < $count; $i++) {
			$act = trim($split[$i]);
			if ($act === "") {
				continue;
			}
			array_push($this->actionAsSet, $act);
		}
		return $this->actionAsSet;
	}
	
	public function toFormatObject() {
		$o = array();
		$o["id"] = $this->id;
		$o["role"] = isset($this->role) ? array("id" => $this->role->getId()) : null;
		$o["menu"] = isset($this->menu) ? array("id" => $this->menu->getId()) : null;
		$o["action"] = $this->action;
		return $o;
	}
}