<?php
namespace App\com\sprint\sms\api\cache;

/*
 * Cache menggunakan extention APC (Untuk PHP 5.3+ pakai yang APCU)
 * Extention ini tidak default di apache, sehingga perlu diinstall manual.
 * Tambahan konfigurasi di php.ini:
 * 	extension=php_apcu.dll
 *	apc.enabled=1
 *	apc.shm_size=32M
 *	apc.ttl=0
 *	apc.enable_cli=1
 *	apc.serializer=php
 *
 */
class ApcuCacheProvider extends Cache 
{
	
	protected function doGet($group, $key) {
		$cacheGroup = apcu_fetch($group);
		if ($cacheGroup == null) {
			return null;
		}
		$list = $cacheGroup['list'];
		$map = $cacheGroup['map'];
		$found = array_search($key, $list);		
		if ($found === false) {
			return null;
		}
		if (!isset($map[$key])) {
			return null;
		}
		$store = $map[$key];
		if ($store != null && !$store->isExpired()) {
			return $store->getValue();
		}
		array_splice($list, $found, 1);
		unset($map[$key]);
		return null;
	}
	
	protected function doPut($group, $key, $value) {
		$cacheGroup = apcu_fetch($group);
		if ($cacheGroup == null) {
			$cacheGroup = array(
				"list" => array(),
				"map" => array()
			);
		}
		$list = $cacheGroup['list'];
		$map = $cacheGroup['map'];
		
		$config = $this->config[$group];
		$age = $config['age'];
		$limit = $config['limit'];
		
		$found = array_search($key, $list);
		if ($found === false) {
			array_push($list, $key);
		}
		$map[$key] = new Store($value, $age);
		$size = count($list);
		if ($size > $limit) {
			$diff = $size - $limit;
			for ($i = 0; $i < $diff; $i++) {
				$ckey = $list[0];
				unset($map[$ckey]);
				array_splice($list, 0, 1);
			}
		}
		$cacheGroup['list'] = $list;
		$cacheGroup['map'] = $map;
		apcu_store($group, $cacheGroup);
	}
	
	protected function doRemove($group, $key) {
		$cacheGroup = apcu_fetch($group);
		if ($cacheGroup == null) {
			return null;
		}
		$list = $cacheGroup['list'];
		$map = $cacheGroup['map'];
		
		$found = array_search($key, $list);
		if ($found !== false) {
			array_splice($list, $found);
		}		
		if (!isset($map[$key])) {
			return null;
		}
		$store = $map[$key];
		$value = null;
		if (!$store->isExpired()) {
			$value = $store->getValue();
		}		
		unset($map[$key]);
		$cacheGroup['list'] = $list;
		$cacheGroup['map'] = $map;
		apcu_store($group, $cacheGroup);
		return $value;
	}
	
	protected function doClear($group) {
		$cacheGroup = apcu_fetch($group);
		if ($cacheGroup == null) {
			return;
		}
		apcu_store($group, array("list" => array(), "map" => array()));
	}	
	
}