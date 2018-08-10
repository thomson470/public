<?php
namespace App\com\sprint\sms\api\access;

use \App\com\sprint\sms\api\support\FormatSupport;

class RoleAccess implements FormatSupport
{
	private $id;
	
	private $name;

	private $menuList = array();
	
	private $menuMap = array();
	
	private $pathMap = array();
	
	
	public function getId() {
		return $this->id;
	}

	public function setId($id) {
		$this->id = $id;
	}

	public function getName() {
		return $this->name;
	}

	public function setName($name) {
		$this->name = $name;
	}

	public function getMenuList() {
		return $this->menuList;
	}

	public function setMenuList(array $menuList = array()) {
		$this->menuList = $menuList;
	}

	public function getMenuMap() {
		return $this->menuMap;
	}

	public function setMenuMap(array $menuMap = array()) {
		$this->menuMap = $menuMap;
	}

	public function getPathMap() {
		return $this->pathMap;
	}

	public function setPathMap(array $pathMap = array()) {
		$this->pathMap = $pathMap;
	}

	public function isAllowAccess($path) {
		if ($path === null || strlen($path) === 0) {
			return false;
		}
		$bos = substr($path, 0, 1) === "/";
		if (!$bos) {
			$path = "/" . $path;
		}
		$parent = substr($path, 1, strlen($path));
		$pos = strpos($parent, "/", 0);
		if ($pos) {
			$parent = substr($parent, 0, $pos);
		}
		$parent = "/" . $parent;
		if (!isset($this->pathMap[$parent])) {
			return false;
		}
		$pathSet = $this->pathMap[$parent];	
		if (count($pathSet) === 0) {
			return $path === $parent;
		}
		return array_search($path, $pathSet) !== false;
	}
	
	public function toFormatObject() {
		$o = array();
		$o["id"] = $this->id;
		$o["name"] = $this->name;
		$o["menuList"] = $this->menuList;
		$o["menuMap"] = $this->menuMap;	
		$o["pathMap"] = $this->pathMap;
		return $o;
	}
	
}
