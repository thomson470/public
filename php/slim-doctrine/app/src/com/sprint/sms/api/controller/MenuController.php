<?php
namespace App\com\sprint\sms\api\controller;

use \App\com\sprint\sms\api\bean\Response;
use \App\com\sprint\sms\api\util\RequestUtil;
use \App\com\sprint\sms\api\util\ObjectUtil;
use \App\com\sprint\sms\api\cache\Constant;
use \App\com\sprint\sms\api\domain\Menu;
use \App\com\sprint\sms\api\dao\MenuDao;
use \App\com\sprint\sms\api\base\BaseController;

class MenuController extends BaseController
{
	public function all() {
		$result = $this->getMenuDao()->getList();
		return Response::SUCCESS($result);
	}
	
	public function view() {
		$request = $this->getRequest();
		$id = RequestUtil::paramsToId($request);
		if (!isset($id)) {
			return Response::ERROR_CODE("01", "id is required");
		}
		$entity = $this->getMenuDao()->get($id);
		return Response::SUCCESS($entity);
	}
	
	public function create() {
		$request = $this->getRequest();
		$entity = RequestUtil::paramsToObject($request, Menu::class);
		$parentObj = $entity->getParent();
		$parentId = $parentObj !== null ? $parentObj->getId() : null;
		$parentId = $parentId !== null ? trim($parentId) : "";
		if ($parentId !== "") {
			$parentObj = $this->getMenuDao()->get($parentId);
			if ($parentObj === null) {
				return Response::ERROR_CODE("01", "Parent is not found");
			}
		} else {
			$parentObj = null;
		}
		$entity->setParent($parentObj);
		$entity = $this->getMenuDao()->save($entity);
		return Response::SUCCESS($entity); 
	}
	
	public function update() {
		$request = $this->getRequest();
		$new = RequestUtil::paramsToObject($request, Menu::class);
		$id = $new->getId();
		if (!isset($id)) {
			return Response::ERROR_CODE("01", "id is required");
		}
		$old = $this->getMenuDao()->get($id);
		if ($old == null) {
			return Response::ERROR_CODE("02", "Menu is not found");
		}
		$parentObj = $new->getParent();
		$parentId = $parentObj !== null ? $parentObj->getId() : null;
		if ($parentId !== null) {
			$parentId = $parentId !== null ? trim($parentId) : "";
			if ($parentId !== "") {
				if ($old->getParent() === null || $parentId !== $old->getParent()->getId()) {
					$parentObj = $this->getMenuDao()->get($parentId);
					if ($parentObj === null) {
						return Response::ERROR_CODE("03", "Parent is not found");
					}
					$old->setParent($parentObj);
				}
			} else {
				$old->setParent();
			}			
		}		
		$ignoredField = array("parent", "priority");
		$ignoredField = array_merge($ignoredField, ObjectUtil::getDefaultIgnoredField());
		ObjectUtil::copy($old, $new, $ignoredField, array(
			"title" => array(ObjectUtil::NOT_NULL, ObjectUtil::NOT_EMPTY),
			"link" => array(ObjectUtil::NOT_NULL),
			"icon" => array(ObjectUtil::NOT_NULL),
			"description" => array(ObjectUtil::NOT_NULL),
			"active" => array(ObjectUtil::NOT_NULL),
			"global" => array(ObjectUtil::NOT_NULL),
			"action" => array(ObjectUtil::NOT_NULL)
		));				
		$entity = $this->getMenuDao()->save($old);		
		return Response::SUCCESS($entity);
	}
	
	public function delete() {
		$request = $this->getRequest();
		$id = RequestUtil::paramsToId($request);
		if (!isset($id)) {
			return Response::ERROR_CODE("01", "id is required");
		}
		$entity = $this->getMenuDao()->delete($id);
		return Response::SUCCESS($entity);
	}
	
	public function sort() {
		$request = $this->getRequest();
		$id = RequestUtil::paramsToId($request);		
		$up = $request->getParam("up");
		$moveUp = "1" === $up || "true" === $up;
		$result = $this->getMenuDao()->sort($id, $moveUp);
		return Response::SUCCESS($result ? 1 : 0);
	}
	
	private function getMenuDao() {
		return new MenuDao($this->getEntityManager(), $this->getCache(), $this->getLogger());
	}
	
}