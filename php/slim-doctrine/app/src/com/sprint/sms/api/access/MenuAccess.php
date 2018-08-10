<?php
namespace App\com\sprint\sms\api\access;

use \App\com\sprint\sms\api\support\FormatSupport;

class MenuAccess implements FormatSupport
{	
	private $id;
	
	private $title;
	
	private $link;
	
	private $icon;
	
	private $description;
	
	private $parent;
	
	private $children = array();
	
	private $action = array();
	
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
	
	public function setParent(MenuAccess $parent = null) {
		$this->parent = $parent;
	}
	
	public function getParent() {
		return $this->parent;
	}	
	
	public function setChildren(array $children = array()) {
		$this->children = $children;
	}
	
	public function getChildren() {
		return $this->children;
	}	
	
	public function setAction(array $action = array()) {
		$this->action = $action;
	}
	
	public function getAction() {
		return $this->action;
	}	
	
	public function toFormatObject() {
		$o = array();
		$o["id"] = $this->id;
		$o["title"] = $this->title;
		$o["link"] = $this->link;	
		$o["icon"] = $this->icon;	
		$o["description"] = $this->description;	
		$o["parent"] = isset($this->parent) ? array("id" => $this->parent->getId()) : null;	
		$o["action"] = $this->action;
		if (isset($this->children)) {
			$o["children"] = array();
			for ($i = 0; $i < count($this->children); $i++) {				
				$o["children"][$i] = $this->children[$i]->toFormatObject();
			}
		}
		return $o;
	}	
}
