<?php
namespace App\com\sprint\sms\api\domain;

use Doctrine\ORM\Mapping as ORM;

use \App\com\sprint\sms\api\base\domain\BaseEntryVersion;
use \App\com\sprint\sms\api\support\FormatSupport;

/**
 * @ORM\Entity
 * @ORM\Table(name = "t_menu")
 */
class Menu extends BaseEntryVersion implements FormatSupport
{

	/**
     * @ORM\Column(name = "ID_", type = "bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy = "IDENTITY")
	 */
	private $id;
	
	/**
     * @ORM\Column(name = "title", type = "string", length = 150, nullable = false)
     */
    private $title;
	
	/**
     * @ORM\Column(name = "link", type = "string", nullable = true)
     */
    private $link;
	
	/**
     * @ORM\Column(name = "icon", type = "string", nullable = true)
     */
    private $icon;
	
	/**
     * @ORM\Column(name = "description", type = "string", nullable = true)
     */
    private $description;
	
	/**
     * @ORM\Column(name = "active", type = "boolean", nullable = false, options = {"default":true})
     */
    private $active;
	
	/**
     * //ORM\ManyToOne(targetEntity = "Menu", inversedBy = "children")
	 * @ORM\ManyToOne(targetEntity = "Menu")
     * @ORM\JoinColumn(name = "f_parent", referencedColumnName = "ID_", onDelete = "SET NULL", nullable = true)
     */
    private $parent;
	
	/**
     * @ORM\Column(name = "priority", type = "bigint", nullable = false)
     */
    private $priority;
	
	/**
     * @ORM\Column(name = "global", type = "boolean", nullable = false, options = {"default":false})
     */
    private $global;
	
	
	private $children;
	
	private $action;
	
	
	public function setId($id) {
		$this->id = $id;
	}
	
	public function getId() {
		return $this->id;
	}
	
	public function setTitle($title) {
		$this->title = $title;
	}
	
	public function getTitle() {
		return $this->title;
	}	
	
	public function setLink($link) {
		$this->link = $link;
	}
	
	public function getLink() {
		return $this->link;
	}
	
	public function setIcon($icon) {
		$this->icon = $icon;
	}
	
	public function getIcon() {
		return $this->icon;
	}
	
	public function setDescription($description) {
		$this->description = $description;
	}
	
	public function getDescription() {
		return $this->description;
	}
	
	public function setActive($active) {
		$this->active = $active;
	}
	
	public function getActive() {
		return $this->active;
	}
	
	public function setParent(Menu $parent = null) {
		$this->parent = $parent;
	}
	
	public function getParent() {
		return $this->parent;
	}
	
	public function setPriority($priority) {
		$this->priority = $priority;
	}
	
	public function getPriority() {
		return $this->priority;
	}
	
	public function setGlobal($global) {
		$this->global = $global;
	}
	
	public function getGlobal() {
		return $this->global;
	}	
	
	public function setChildren($children) {
		$this->children = $children;
	}
	
	public function getChildren() {
		return $this->children;
	}	
	
	public function setAction(array $action = null) {
		$this->action = $action;
	}
	
	public function getAction() {
		return $this->action;
	}
	
	public function toFormatObject() {
		$o = parent::toFormatObject();
		$o["id"] = $this->id;
		$o["title"] = $this->title;
		$o["link"] = $this->link;	
		$o["icon"] = $this->icon;	
		$o["description"] = $this->description;	
		$o["active"] = $this->active ? 1 : 0;	
		$o["parent"] = $this->parent !== null ? array("id" => $this->parent->getId()) : null;	
		$o["priority"] = $this->priority;	
		$o["global"] = $this->global ? 1 : 0;	
		$o["action"] = $this->action;		
		if (isset($this->children)) {
			$o["children"] = array();
			for ($i = 0; $i < count($this->children); $i++) {
				$c[Menu::VERSION] = $this->children[$i]->getVersion();
				$c[Menu::ENTRY] = $this->children[$i]->getEntryTime() !== null ? $this->children[$i]->getEntryTime()->getTimestamp() * 1000 : null;
				$c["id"] = $this->children[$i]->id;
				$c["title"] = $this->children[$i]->title;
				$c["link"] = $this->children[$i]->link;	
				$c["icon"] = $this->children[$i]->icon;	
				$c["description"] = $this->children[$i]->description;	
				$c["active"] = $this->children[$i]->active ? 1 : 0;	
				$c["parent"] = $this->children[$i]->parent !== null ? array("id" => $this->children[$i]->parent->getId()) : null;	
				$c["priority"] = $this->children[$i]->priority;	
				$c["global"] = $this->children[$i]->global ? 1 : 0;	
				$c["action"] = $this->children[$i]->action;
				$o["children"][$i] = $c;
			}
		}		
		return $o;
	}
	
}