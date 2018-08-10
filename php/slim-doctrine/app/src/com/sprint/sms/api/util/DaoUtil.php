<?php
namespace App\com\sprint\sms\api\util;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \App\com\sprint\sms\api\bean\Page;
use \App\com\sprint\sms\api\support\FormatSupport as Entity;
use \App\com\sprint\sms\api\util\ObjectUtil;
use \App\com\sprint\sms\api\support\SoftDeleteSupport;
use \App\com\sprint\sms\api\base\domain\BaseVersion;
use \App\com\sprint\sms\api\base\domain\BaseEntry;
use \App\com\sprint\sms\api\base\domain\BaseEntryVersion;
use \Doctrine\ORM\EntityManager;
use \Doctrine\ORM\Tools\Pagination\Paginator;

final class DaoUtil
{

	const VAR_ENTITY_MANAGER	= "EntityManager";
	const VAR_PAGE				= "Page";
	const VAR_ENTITY_CLASS		= "EntityClass";
	const VAR_ORDER_BY			= "OrderBy";
	const VAR_GROUP_BY			= "GroupBy";
	const VAR_ALIAS				= "Alias";
	const VAR_CRITERIA			= "Criteria";
	const VAR_WHERE_QUERY		= "WhereQuery";
	const VAR_WHERE_PARAM		= "WhereParam";
	const VAR_LOG				= "Log";
	
	const PRINT_QUERY			= true;
	const DEFAULT_ALIAS			= "e";

	// NOTE: 
	//   - Pencarian untuk entity tertentu, dan hasilnya dalam bentuk paging atau array.
	//   - isi $criteria selalu sama dengan (" WHERE name=:name AND active=:active")
	// TODO: 
	//   - $criteria adalah where condition, sebaiknya dibuat util untuk mengenerate $criteria dari $entity yang disupply dari request.
	//   - Jika metode generate $criteria sudah ada, sebaiknya $entity ditempatkan di $page->data.
	public static function search(array $var) {
		$entityManager = $var[self::VAR_ENTITY_MANAGER];
		$entityClass = $var[self::VAR_ENTITY_CLASS];
		$page = isset($var[self::VAR_PAGE]) ? $var[self::VAR_PAGE] : null;		
		$orderBy = isset($var[self::VAR_ORDER_BY]) ? $var[self::VAR_ORDER_BY] : null;		
		$criteria = isset($var[self::VAR_CRITERIA]) ? $var[self::VAR_CRITERIA] : null;
		if (!isset($criteria)) {
			$criteria = array();
		}
		if (ObjectUtil::isClassOf($entityClass, SoftDeleteSupport::class)) {
			$criteria['deleted'] = 0;
		}
		$repo = $entityManager->getRepository($entityClass);
		if (null != $page) {
			if ($page->getIndex() == null) {
				$page->setIndex(1);
			}
			if ($page->getSize() == null) {
				$page->setSize(Page::DEFAULT_ROWS_PER_PAGE);
			}
			$count = $repo->findBy($criteria);
			$records = count($count);
			$page->setRecords($records);
			if ($records == 0) {
				return $page;
			}
			$data = $repo->findBy($criteria, $orderBy, $page->getSize(), ($page->getIndex() - 1) * $page->getSize());
			$page->setData($data);
			return $page;
		} else {
			$result = $repo->findBy($criteria, $orderBy);
			return $result;
		}
	}
	
	// NOTE: 
	//   - Pencarian untuk entity tertentu, dan hasilnya dalam bentuk paging.
	//   - $alias adalah alias entity (gunakan di $whereQuery), cth: a
	//   - isi $whereQuery berisi kondisi pencarian, cth: a.title=:title
	//   - $whereParam adalah daftar parameter yang didefinisikan di $whereQuery, cth: array("title" => "Judul")
	public static function query(array $var)
	{
		$entityManager = $var[self::VAR_ENTITY_MANAGER];
		$entityClass = $var[self::VAR_ENTITY_CLASS];
		$page = isset($var[self::VAR_PAGE]) ? $var[self::VAR_PAGE] : null;		
		$alias = isset($var[self::VAR_ALIAS]) ? $var[self::VAR_ALIAS] : self::DEFAULT_ALIAS;
		$orderBy = isset($var[self::VAR_ORDER_BY]) ? $var[self::VAR_ORDER_BY] : null;
		$log = isset($var[self::VAR_LOG]) ? $var[self::VAR_LOG] : null;
		
		$repo = $entityManager->getRepository($entityClass);
		if (null !== $page) {
			if ($page->getIndex() === null) {
				$page->setIndex(1);
			}
			if ($page->getSize() === null) {
				$page->setSize(Page::DEFAULT_ROWS_PER_PAGE);
			}
			$query = self::createQuery($repo, $var);
			$query->select("COUNT(".$alias.")");
			$query = $query->getQuery();
			if (self::PRINT_QUERY && null !== $log) {
				$log->debug($query->getSql());
			}
			$records = $query->getSingleScalarResult();
			$page->setRecords($records);
			if ($records === 0) {
				return $page;
			}
		}		
		$query = self::createQuery($repo, $var);
		if (null !== $orderBy) {
			foreach($orderBy as $key => $val) {
				$query->addOrderBy($key, $val);
			}			
		}		
		if (null !== $page) {
			$query->setFirstResult(($page->getIndex() - 1) * $page->getSize());
			$query->setMaxResults($page->getSize());
		}
		$query = $query->getQuery();
		if (self::PRINT_QUERY && null !== $log) {
			$log->debug($query->getSql());
		}
		$result = $query->getResult();
		if (null !== $page) {
			$page->setData($result);
			return $page;
		} else {
			return $result;
		}
	}
	
	private static function createQuery($repo, array $var) {
		$entityClass = $var[self::VAR_ENTITY_CLASS];
		$alias = isset($var[self::VAR_ALIAS]) ? $var[self::VAR_ALIAS] : self::DEFAULT_ALIAS;
		$whereQuery = isset($var[self::VAR_WHERE_QUERY]) ? $var[self::VAR_WHERE_QUERY] : null;
		$whereParam = isset($var[self::VAR_WHERE_PARAM]) ? $var[self::VAR_WHERE_PARAM] : null;
		$groupBy = isset($var[self::VAR_GROUP_BY]) ? $var[self::VAR_GROUP_BY] : null;		
		$query = $repo->createQueryBuilder($alias);
		if (null !== $whereQuery) {
			if (ObjectUtil::isClassOf($entityClass, SoftDeleteSupport::class)) {
				$whereQuery = $whereQuery . " AND " . $alias . ".deleted=:deleted";
				$whereParam['deleted'] = 0;
			}					
		} else {
			if (ObjectUtil::isClassOf($entityClass, SoftDeleteSupport::class)) {
				$whereQuery = $alias . ".deleted=:deleted";
				$whereParam = array("deleted" => 0);
			}
		}
		if (null !== $whereQuery) {
			$query->where($whereQuery);
			if (null !== $whereParam) {
				foreach($whereParam as $key => $val) {
					$query->setParameter($key, $val);
				}
			}
		}
		if (null !== $groupBy) {
			$query->groupBy($groupBy);
		}
		return $query;
	}	
	
	// NOTE: Untuk melakukan pencarian HANYA satu object dengan kondisi pencarian sesuai $criteria
	// TODO: Perlu dibuat otomatis $criteria berdasarkan input request.
	public static function unique(EntityManager $em, $entityClass, array $criteria = null) {
		if (!isset($criteria)) {
			$criteria = array();
		}
		$repo = $em->getRepository($entityClass);
		$result = $repo->findOneBy($criteria);
		return $result;
	}
	
	// NOTE: Pencarian entity berdasarkan id
	public static function single(EntityManager $em, $entityClass, $id) {
		$repo = $em->getRepository($entityClass);
		$result = $repo->find($id);
		return $result;
	}
	
	// NOTE: untuk menyimpan entity ke table
	public static function save(EntityManager $em, Entity $entity) {
		$entityClass = get_class($entity);
		if (ObjectUtil::isClassOf($entityClass, BaseEntry::class) || 
			ObjectUtil::isClassOf($entityClass, BaseEntryVersion::class)) 
		{
			if ($entity->getEntryTime() === null) {
				$entity->setEntryTime(new \DateTime());
			}
		}
		$em->persist($entity);
        $em->flush();
		return $entity;
	}	
	
	// NOTE: menghapus $entity
	public static function delete(EntityManager $em, Entity $entity) {
		$entityClass = get_class($entity);
		if (ObjectUtil::isClassOf($entityClass, SoftDeleteSupport::class)) {
			$entity->setDeleted(true);
			$em->persist($entity);
		} else {
			$em->remove($entity);
		}
        $em->flush();		
		return $entity;
	}
	
	// NOTE: menghapus $entity berdasarkan $id
	public static function deleteById(EntityManager $em, $entityClass, $id) {
		$entity = self::single($em, $entityClass, $id);
		if ($entity === null) {
			return null;
		}
		return self::delete($em, $entity);
	}
	
}
