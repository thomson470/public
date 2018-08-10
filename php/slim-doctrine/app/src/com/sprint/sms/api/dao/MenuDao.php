<?php
namespace App\com\sprint\sms\api\dao;

use \App\com\sprint\sms\api\base\BaseDao;
use \App\com\sprint\sms\api\bean\Page;
use \App\com\sprint\sms\api\domain\Menu;
use \App\com\sprint\sms\api\domain\RoleMenu;
use \App\com\sprint\sms\api\util\AppConstant;
use \App\com\sprint\sms\api\util\DaoUtil;
use \App\com\sprint\sms\api\util\ObjectUtil;
use \App\com\sprint\sms\api\support\SoftDeleteSupport;

class MenuDao extends BaseDao
{
	
	public function getList() {
		$orderBy = array ("parent" => "ASC", "priority" => "ASC");
		$list = DaoUtil::search(array(
			DaoUtil::VAR_ENTITY_MANAGER => $this->getEntityManager(),
			DaoUtil::VAR_ENTITY_CLASS => Menu::class,
			DaoUtil::VAR_ORDER_BY => $orderBy
		));
		if ($list === null) {
			return null;
		}
		
		// sort dengan parent null diawal (kasus oracle yg null berada diakhir)
		usort($list, function ($a, $b) {
			if ($a->getParent() === null) {
		        return ($b->getParent() === null) ? 0 : -1;
		    }
		    if ($b->getParent() === null) {
		        return 1;
		    }
		    return strcmp($b->getParent()->getPriority(), $a->getParent()->getPriority());
		});
		
		$menus = array();
		$struct = array();
		$count = count($list);
		for ($i = 0; $i < $count; $i++) {
			$m = $list[$i];
			$menus[$m->getId()] = $m;
			if ($m->getParent() === null) {
				$struct[$m->getId()] = array();
			} else {
				if ($m->getParent()->getParent() !== null) {
					$mp;
					if (!isset($struct[$m->getParent()->getParent()->getId()])) {
						$mp = array();						
					} else {
						$mp = $struct[$m->getParent()->getParent()->getId()];
					}
					$ls;
					if (!isset($mp[$m->getParent()->getId()])) {
						$ls = array();						
					} else {
						$ls = $mp[$m->getParent()->getId()];
					}
					array_push($ls, $m->getId());
					$mp[$m->getParent()->getId()] = $ls;
					$struct[$m->getParent()->getParent()->getId()] = $mp;		
				} else {
					$struct[$m->getParent()->getId()][$m->getId()] = array();
				}
			}
		}
		unset($list);
		
		$result = array();
		foreach($struct as $pKey1 => $map) {
			$pMenu1 = $menus[$pKey1];
			$cMenu1 = array();
			foreach($map as $pKey2 => $chds) {
				$pMenu2 = $menus[$pKey2];
				$cMenu2 = array();				
				for ($i = 0; $i < count($chds); $i++) {
					$chdMenu = $menus[$chds[$i]];
					array_push($cMenu2, $chdMenu);
				}
				$pMenu2->setChildren($cMenu2);
				array_push($cMenu1, $pMenu2);
			}
			$pMenu1->setChildren($cMenu1);
			array_push($result, $pMenu1);
		}
		unset($menus);
		unset($struct);
		
		usort($result, function ($a, $b) {
			return strcmp($a->getPriority(), $b->getPriority());
		});
		for ($i = 0; $i < count($result); $i++) {
			$child1 = $result[$i]->getChildren();
			usort($child1, function ($a, $b) {
				return strcmp($a->getPriority(), $b->getPriority());
			});
			for ($j = 0; $j < count($child1); $j++) {
				$child2 = $child1[$j]->getChildren();
				usort($child2, function ($a, $b) {
					return strcmp($a->getPriority(), $b->getPriority());
				});
				$child1[$j]->setChildren($child2);
			}
			$result[$i]->setChildren($child1);
		}
		return $result;
	}
	
	public function getListByRoleId($roleId, $active = null) 
	{	
		$menus = array();
		$struct = array();
		$param = array();
		$mids = array();
		$actions = array();
		$em = $this->getEntityManager();
		$repo = $em->getRepository(RoleMenu::class);
		if ($roleId !== null) {
			$qb = $repo->createQueryBuilder("o")->join("o.role", "r")->join("o.menu", "m");
			$param = array();
			$where = "r.id=:roleId";
			$param["roleId"] = $roleId;
			if ($active !== null) {
				$where = $where . " AND m.active=:active";
				$param["active"] = $active;
			}
			$qb->where($where);
			foreach($param as $key => $val) {
				$qb->setParameter($key, $val);
			}
			$qb = $qb->getQuery();
			$list = $qb->getResult();			
			if ($list !== null) {			
				$count = count($list);
				for ($i = 0; $i < $count; $i++) {
					$m = $list[$i]->getMenu();
					array_push($mids, $m->getId());
					$actions[$m->getId()] = $list[$i]->getActionAsSet();
				}
				unset($list);
			}
		}		
		$repo = $em->getRepository(Menu::class);
		$qb = $repo->createQueryBuilder("m");
		$param = array();
		$where = "(m.id IN (:ids) OR m.global=:global)";
		$param["ids"] = $mids;
		$param["global"] = true;
		if ($active !== null) {
			$where = $where . " AND m.active=:active";
			$param["active"] = $active;
		}	
		$qb->where($where);
		foreach($param as $key => $val) {
			$qb->setParameter($key, $val);
		}
		$qb->addOrderBy("m.parent", "ASC")->addOrderBy("m.priority", "ASC");
		$qb = $qb->getQuery();
		
		$list = $qb->getResult();
		if ($list === null) {
			return null;
		}
		// sort dengan parent null diawal (kasus oracle yg null berada diakhir)
		usort($list, function ($a, $b) {
			if ($a->getParent() === null) {
				return ($b->getParent() === null) ? 0 : -1;
			}
			if ($b->getParent() === null) {
				return 1;
			}
			return strcmp($b->getParent()->getPriority(), $a->getParent()->getPriority());
		});
		$menus = array();
		$struct = array();
		$count = count($list);
		for ($i = 0; $i < $count; $i++) {
			$m = $list[$i];
			if (isset($actions[$m->getId()])) {
				$m->setAction($actions[$m->getId()]);
			}
			$menus[$m->getId()] = $m;
			if ($m->getParent() === null) {
				$struct[$m->getId()] = array();
			} else {
				if ($m->getParent()->getParent() !== null) {
					$mp;
					if (!isset($struct[$m->getParent()->getParent()->getId()])) {
						$mp = array();						
					} else {
						$mp = $struct[$m->getParent()->getParent()->getId()];
					}
					$ls;
					if (!isset($mp[$m->getParent()->getId()])) {
						$ls = array();						
					} else {
						$ls = $mp[$m->getParent()->getId()];
					}
					array_push($ls, $m->getId());
					$mp[$m->getParent()->getId()] = $ls;
					$struct[$m->getParent()->getParent()->getId()] = $mp;		
				} else {
					$struct[$m->getParent()->getId()][$m->getId()] = array();
				}
			}
		}
		unset($list);
		
		$result = array();
		foreach($struct as $pKey1 => $map) {
			$pMenu1 = $menus[$pKey1];
			$cMenu1 = array();
			foreach($map as $pKey2 => $chds) {
				$pMenu2 = $menus[$pKey2];
				$cMenu2 = array();				
				for ($i = 0; $i < count($chds); $i++) {
					$chdMenu = $menus[$chds[$i]];
					array_push($cMenu2, $chdMenu);
				}
				$pMenu2->setChildren($cMenu2);
				array_push($cMenu1, $pMenu2);
			}
			$pMenu1->setChildren($cMenu1);
			array_push($result, $pMenu1);
		}
		unset($menus);
		unset($struct);
		
		usort($result, function ($a, $b) {
			return strcmp($a->getPriority(), $b->getPriority());
		});
		for ($i = 0; $i < count($result); $i++) {
			$child1 = $result[$i]->getChildren();
			usort($child1, function ($a, $b) {
				return strcmp($a->getPriority(), $b->getPriority());
			});
			for ($j = 0; $j < count($child1); $j++) {
				$child2 = $child1[$j]->getChildren();
				usort($child2, function ($a, $b) {
					return strcmp($a->getPriority(), $b->getPriority());
				});
				$child1[$j]->setChildren($child2);
			}
			$result[$i]->setChildren($child1);
		}
		return $result;
	}
	
	public function get($id) {
		return DaoUtil::single($this->getEntityManager(), Menu::class, $id);
	}
	
	public function sort($id, $moveUp) {
		$this->getCache()->clear(AppConstant::CACHE_ACCESS_ROLE);
		$m1 = DaoUtil::single($this->getEntityManager(), Menu::class, $id);
		if ($m1 === null) {
			return false;
		}
		$entityClass = Menu::class;
		$where = "1=1";
		$param = array();
		$alias = "m";
		if ($m1->getParent() != null) {
			$where = $where . " AND " . $alias . ".parent=:parent";
			$param["parent"] = $m1->getParent();
		} else {
			$where = $where . " AND " . $alias . ".parent IS NULL";
		}
		if (ObjectUtil::isClassOf($entityClass, SoftDeleteSupport::class)) {
			$where = $where . " AND " . $alias . ".deleted=:deleted";
			$param['deleted'] = 0;
		}
		$orderBy = array();
		if ($moveUp) {
			$where = $where . " AND " . $alias . ".priority<:priority";	
			$orderBy[$alias . ".priority"] = "DESC";
		} else {
			$where = $where . " AND " . $alias . ".priority>:priority";
			$orderBy[$alias . ".priority"] = "ASC";
		}
		$param["priority"] = $m1->getPriority();
		
		$em = $this->getEntityManager();
		$repo = $em->getRepository($entityClass);
		$query = $repo->createQueryBuilder($alias);
		$query->where($where);
		foreach($param as $key => $val) {
			$query->setParameter($key, $val);
		}
		foreach($orderBy as $key => $val) {
			$query->addOrderBy($key, $val);
		}		
		$query->setMaxResults(1);
		$query = $query->getQuery();
		$m2 = $query->getOneOrNullResult();		
		if ($m2 == null) {
			return false;
		}
		$priority1 = $m1->getPriority();
		$priority2 = $m2->getPriority();
		$m1->setPriority($priority2);
		$m2->setPriority($priority1);
		$em->persist($m1);
		$em->persist($m2);
		$em->flush();
		return true;
	}
	
	public function save(Menu $menu) {
		$this->getCache()->clear(AppConstant::CACHE_ACCESS_ROLE);
		$id = $menu->getId();
		if ($id === null || $id === '') {
			$repo = $this->getEntityManager()->getRepository(Menu::class);
			$qb = $repo->createQueryBuilder('m');
			$qb->select('MAX(m.priority)');  
			$priority = $qb->getQuery()->getSingleScalarResult();
			if ($priority === null) {
				$priority = 1;
			}
			$priority = $priority + 1;
			$menu->setPriority($priority);
		}
		return DaoUtil::save($this->getEntityManager(), $menu);
	}
	
	public function delete($id) {
		$this->getCache()->clear(AppConstant::CACHE_ACCESS_ROLE);
		return DaoUtil::deleteById($this->getEntityManager(), Menu::class, $id);
	}
}