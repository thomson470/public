<?php
namespace App\com\sprint\sms\api\controller;

use \App\com\sprint\sms\api\util\RequestUtil;
use \App\com\sprint\sms\api\util\ObjectUtil;
use \App\com\sprint\sms\api\bean\Response;
use \App\com\sprint\sms\api\domain\User;
use \App\com\sprint\sms\api\dao\RoleDao;
use \App\com\sprint\sms\api\dao\UserDao;
use \App\com\sprint\sms\api\base\BaseController;

class UserController extends BaseController
{	
	public function search() {
		$request = $this->getRequest();
		$page 	= RequestUtil::paramsToPage($request, User::class);
		$user 	= RequestUtil::paramsToObject($request, User::class);
		$order 	= RequestUtil::paramsToOrder($request, User::class);
		$result = $this->getUserDao()->page($page, $user, $order);
		return Response::SUCCESS($result);
	}
	
	public function view() {
		$request = $this->getRequest();
		$id = RequestUtil::paramsToId($request);
		if (!isset($id)) {
			return Response::ERROR_CODE("01", "id is required");
		}
		$entity = $this->getUserDao()->get($id);
		return Response::SUCCESS($entity);
	}
	
	public function create() {
		$request = $this->getRequest();
		$entity = RequestUtil::paramsToObject($request, User::class);
		$userName = $entity->getName();
		if ($userName === null || trim($userName) === "") {
			return Response::ERROR_CODE("01", "name is required");
		}
		$password = $entity->getPassword();
		if ($password === null || trim($password) === "") {
			return Response::ERROR_CODE("02", "password is required");
		}
		$userDao = $this->getUserDao();
		$user = $userDao->getByName($userName);
		if ($user != null) {
			return Response::ERROR_CODE("03", "User name has been exist");
		}		
		$roleObj = $entity->getRole();
		$roleId = $roleObj !== null ? $roleObj->getId() : null;
		if ($roleId === null) {
			return Response::ERROR_CODE("04", "Role Id is required");
		}
		$roleObj = $this->getRoleDao()->get($roleId);
		if ($roleObj === null) {
			return Response::ERROR_CODE("05", "Role is not found");
		}
		if ($entity->getAvatar() === null) {
			$entity->setAvatar(false);
		}
		$entity->setRole($roleObj);
		$password = hash("sha256", $password);
		$entity->setPassword($password);		
		$entity = $userDao->save($entity);
		return Response::SUCCESS($entity);
	}
	
	public function update() {
		$request = $this->getRequest();
		$new = RequestUtil::paramsToObject($request, User::class);
		$id = $new->getId();
		if (!isset($id)) {
			return Response::ERROR_CODE("01", "id is required");
		}
		$userDao = $this->getUserDao();
		$old = $userDao->get($id);
		if ($old === null) {
			return Response::ERROR_CODE("02", "User is not found");
		}
		$roleObj = $new->getRole();
		$roleId = $roleObj !== null ? $roleObj->getId() : "";
		if ("" !== $roleId && $old->getRole()->getId() != $roleId) {
			$roleObj = $this->getRoleDao()->get($roleId);
			if (null === $roleObj) {
				return Response::ERROR_CODE("03", "Role is not found");
			}
			$old->setRole($roleObj);
		}
		$password = $new->getPassword();
		if ($password !== null && trim($password) !== "") {
			if ($password !== $old->getPassword()) {
				$password = hash("sha256", $password);
				$old->setPassword($password);
			}
		}
		$ignoredField = array("role", "name", "password");
		$ignoredField = array_merge($ignoredField, ObjectUtil::getDefaultIgnoredField());
		ObjectUtil::copy($old, $new, $ignoredField, array(
			"firstName" => array(ObjectUtil::NOT_NULL, ObjectUtil::NOT_EMPTY),
			"active" => array(ObjectUtil::NOT_NULL),
			"lastName" => array(ObjectUtil::NOT_NULL),
			"email" => array(ObjectUtil::NOT_NULL, ObjectUtil::NOT_EMPTY),
			"phone" => array(ObjectUtil::NOT_NULL),
			"avatar" => array(ObjectUtil::NOT_NULL)
		));				
		$entity = $userDao->save($old);		
		return Response::SUCCESS($entity);
		
	}
	
	public function delete() {
		$request = $this->getRequest();
		$id = RequestUtil::paramsToId($request);
		if (!isset($id)) {
			return Response::ERROR_CODE("01", "id is required");
		}
		$entity = $this->getUserDao()->delete($id);
		return Response::SUCCESS($entity);
	}
	
	
	private function getUserDao() {
		return new UserDao($this->getEntityManager(), $this->getCache(), $this->getLogger());
	}
	
	private function getRoleDao() {
		return new RoleDao($this->getEntityManager(), $this->getCache(), $this->getLogger());
	}
	
}