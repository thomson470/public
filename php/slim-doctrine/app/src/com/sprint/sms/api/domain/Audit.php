<?php
namespace App\com\sprint\sms\api\domain;

use Doctrine\ORM\Mapping as ORM;
use \App\com\sprint\sms\api\support\FormatSupport;

/**
 * @ORM\Entity
 * @ORM\Table(name = "t_audit")
 */
class Audit implements FormatSupport
{
	/**
     * @ORM\Column(name="ID_", type = "string")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy = "UUID")
     */
	private $id;
	
	/**
     * @ORM\Column(name="auditor", type = "string")
     */
	private $auditor;
	
	/**
     * @ORM\Column(name = "action", type = "string", length = 100)
     */
	private $action;
	
	/**
     * @ORM\Column(name = "classname", type = "string")
     */
	private $className;
	
	/**
     * @ORM\Column(name = "content", type = "text")
     */
	private $content;
	
	/**
     * @ORM\Column(name = "audit_date", type = "datetime", nullable = false)
     */
	private $auditDate;
	
	
	public function setId($id) {
		$this->id = $id;
	}
	
	public function getId() {
		return $this->id;
	}
	
	public function setAuditor($auditor) {
		$this->auditor = $auditor;
	}
	
	public function getAuditor() {
		return $this->auditor;
	}
	
	public function setAction($action) {
		$this->action = $action;
	}
	
	public function getAction() {
		return $this->action;
	}
	
	public function setClassName($className) {
		$this->className = $className;
	}
	
	public function getClassName() {
		return $this->className;
	}
	
	public function setContent($content) {
		$this->content = $content;
	}
	
	public function getContent() {
		return $this->content;
	}
	
	public function setAuditDate($auditDate) {
		$this->auditDate = $auditDate;
	}
	
	public function getAuditDate() {
		return $this->auditDate;
	}
	
	public function toFormatObject() {
		$o = parent::toFormatObject();
		$o["id"] = $this->id;
		$o["auditor"] = $this->auditor;
		$o["action"] = $this->action;
		$o["classname"] = $this->className;
		$o["content"] = $this->content;
		$o["auditDate"] = $this->auditDate != null ? $this->auditDate->getTimestamp() * 1000 : null;
		return $o;
	}
}
