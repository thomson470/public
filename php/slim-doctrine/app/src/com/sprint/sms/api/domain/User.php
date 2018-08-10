<?php
namespace App\com\sprint\sms\api\domain;

use Doctrine\ORM\Mapping as ORM;
use \App\com\sprint\sms\api\base\domain\BaseEntryVersion;
use \App\com\sprint\sms\api\support\FormatSupport;

/**
 * @ORM\Entity
 * @ORM\Table(name = "t_user")
 */
class User extends BaseEntryVersion implements FormatSupport
{

	/**
     * @ORM\Column(name="ID_", type = "bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy = "IDENTITY")
     */
	private $id;
	
	/**
     * @ORM\Column(name = "name", type = "string", length = 100, unique = true, nullable = false)    
     */
    private $name;
	
	/**
     * @ORM\Column(name = "password", type = "string", nullable = false)    
     */
    private $password;
	
	/**
     * @ORM\Column(name = "firstname", type = "string", length = 100, nullable = false)    
     */
    private $firstName;
	
	/**
     * @ORM\Column(name = "lastname", type = "string", length = 100, nullable = true)    
     */
    private $lastName;
	
	/**
     * @ORM\Column(name = "email", type = "string", length = 100, nullable = true)    
     */
    private $email;
	
	/**
     * @ORM\Column(name = "phone", type = "string", length = 100, nullable = true)    
     */
    private $phone;
	
	/**
     * @ORM\Column(name = "avatar", type = "boolean", nullable = false, options = {"default":false})    
     */
    private $avatar;
	
	/**
     * @ORM\Column(name = "active", type = "boolean", nullable = false, options = {"default":true})
     */
    private $active;
	
	/**
     * @ORM\Column(name = "last_logged_in", type = "datetime", nullable = true)     
     */
	private $lastLoggedIn;
	
	/**
     * @ORM\Column(name = "last_logged_out", type = "datetime", nullable = true)     
     */
	private $lastLoggedOut;
	
	/**
	 * @ORM\ManyToOne(targetEntity = "Role", fetch = "EAGER")
	 * @ORM\JoinColumn(name = "f_role", referencedColumnName = "ID_", nullable = false)
	 */ 
    private $role;
	
	
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
	
	public function setPassword($password) {
		$this->password = $password;
	}
	
	public function getPassword() {
		return $this->password;
	}
	
	public function setFirstName($firstName) {
		$this->firstName = $firstName;
	}
	
	public function getFirstName() {
		return $this->firstName;
	}
	
	public function setLastName($lastName) {
		$this->lastName = $lastName;
	}
	
	public function getLastName() {
		return $this->lastName;
	}

	public function setEmail($email) {
		$this->email = $email;
	}
	
	public function getEmail() {
		return $this->email;
	}
	
	public function setPhone($phone) {
		$this->phone = $phone;
	}
	
	public function getPhone() {
		return $this->phone;
	}
	
	public function setAvatar($avatar) {
		$this->avatar = $avatar;
	}
	
	public function getAvatar() {
		return $this->avatar;
	}
	
	public function setActive($active) {
		$this->active = $active;
	}
	
	public function getActive() {
		return $this->active;
	}
	
	public function setLastLoggedIn($lastLoggedIn) {
		$this->lastLoggedIn = $lastLoggedIn;
	}
	
	public function getLastLoggedIn() {
		return $this->lastLoggedIn;
	}
	
	public function setLastLoggedOut($lastLoggedOut) {
		$this->lastLoggedOut = $lastLoggedOut;
	}
	
	public function getLastLoggedOut() {
		return $this->lastLoggedOut;
	}
	
	public function setRole(Role $role)
    {
        $this->role = $role;
    }
	
    public function getRole()
    {
        return $this->role;
    }
	
	
	public function toFormatObject() {
		$o = parent::toFormatObject();
		$o["id"] = $this->id;
		$o["name"] = $this->name;
		$o["firstName"] = $this->firstName;
		$o["lastName"] = $this->lastName;
		$o["email"] = $this->email;
		$o["phone"] = $this->phone;
		$o["avatar"] = $this->avatar ? 1 : 0;
		$o["active"] = $this->active ? 1 : 0;
		$o["lastLoggedIn"] = $this->lastLoggedIn !== null ? $this->lastLoggedIn->getTimestamp() * 1000 : null;
		$o["lastLoggedOut"] = $this->lastLoggedOut !== null ? $this->lastLoggedOut->getTimestamp() * 1000 : null;
		$o["role"] = $this->role !== null ? $this->role->toFormatObject() : null;
		return $o;
	}
	
}