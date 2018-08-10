<?php
namespace App\com\sprint\sms\api\domain;

use Doctrine\ORM\Mapping as ORM;

use App\com\sprint\sms\api\base\domain\BaseEntry;
use App\com\sprint\sms\api\support\FormatSupport;

use \App\com\sprint\sms\api\domain\User;

/**
 * @ORM\Entity
 * @ORM\Table(name = "t_access")
 */
class Access extends BaseEntry implements FormatSupport
{

	/**
     * @ORM\Column(name="ID_", type = "string", nullable = false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy = "UUID")
     */
	private $id;
	
	/**
	 * @ORM\ManyToOne(targetEntity = "User", fetch = "EAGER")
	 * @ORM\JoinColumn(name = "f_user", referencedColumnName = "ID_", unique = true, nullable = false, onDelete="CASCADE")
	 */ 
    private $user;
	
	/**
     * @ORM\Column(name = "agent", type = "string", length = 1024, nullable = false)
     */
    private $agent;
	
	/**
     * @ORM\Column(name = "expired", type = "bigint", nullable = false, options = {"unsigned":true, "default":0})
     */
	private $expired;
	
	public function setId($id) {
		$this->id = $id;
	}
	
	public function getId() {
		return $this->id;
	}    
	
	public function setUser(User $user)
    {
        $this->user = $user;
    }
	
    public function getUser()
    {
        return $this->user;
    }
	
	public function setAgent($agent)
    {
        $this->agent = $agent;
    }

    public function getAgent()
    {
        return $this->agent;
    }	
	
	public function setExpired($expired)
    {
        $this->expired = $expired;
    }   
	
	public function getExpired()
    {
        return $this->expired;
    }   
	
	public function hasExpired() {
		$now = round(microtime(true) * 1000);
		return $now > $this->expired;
	}
	
	public function toFormatObject() {
		$o = parent::toFormatObject();
		$o["id"] = $this->id;
		$o["user"] = isset($this->user) ? $this->user->toFormatObject() : null;
		$o["agent"] = $this->agent;	
		return $o;
	}
}