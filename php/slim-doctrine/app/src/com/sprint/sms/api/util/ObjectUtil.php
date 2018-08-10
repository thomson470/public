<?php
namespace App\com\sprint\sms\api\util;

use ReflectionClass;
use ReflectionMethod;
use Exception;

final class ObjectUtil
{

	const NOT_NULL			= 1;
	const NOT_EMPTY			= 2;
	const NOT_NUMBER		= 3;
	
	/** @const */
	private static $defaultIgnoredField = array("id", "version", "entryTime");
	
	public static function getDefaultIgnoredField() {
		return self::$defaultIgnoredField;
	}
	

	/*
	 * copy
	 *   - Memindahkan nilai-nilai field dari $src ke $dest
	 *   - $ignore berisi daftar field yang tidak perlu di-copy
	 *   - $rule berisi kondisi dari $src ke $dest, tediri dari: null, empty, dll
	 */
	public static function copy($dest, $src, array $ignore = array(), array $rule = array()) {
		$clsDest = get_class($dest);
		$clsSrc = get_class($src);
		if ($clsDest !== $clsSrc) {
			throw new Exception("Source class is not equal to destination class");
		}
		$class = new ReflectionClass($clsDest);
		$methods = $class->getMethods();
		$count = count($methods);
		for ($i = 0; $i < $count; $i++) {
			$mtd = $methods[$i];
			if (!StringUtil::startsWith($mtd->name, "set")) {
				continue;
			}
			$fld = substr($mtd->name, 3);
			$fld = strtolower(substr($fld, 0, 1)) . substr($fld, 1);
			$getMtd = new ReflectionMethod($class->name, "get" . strtoupper(substr($fld, 0, 1)) . substr($fld, 1));
			$getVal = $getMtd->invoke($src);
			if (self::canCopy($fld, $getVal, $ignore, $rule)) {
				$mtd->invoke($dest, $getVal);
			}
		}
	}
	
	private static function canCopy($field, $val, array $ignore = array(), array $rule = array()) 
	{
		if (in_array($field, $ignore)) {
			return false;
		}
		if (!isset($rule[$field])) {
			return true;
		}
		$check = $rule[$field];
		$count = count($check);
		$result = true;
		for ($i = 0; $i < $count; $i++) {
			if(!$result) {
				return $result;
			}
			if ($check[$i] === self::NOT_NULL && $val === null) {
				$result = false;
			}
			else if ($check[$i] === self::NOT_EMPTY) {
				if ($val === null || trim($val) === "") {
					$result = false;
				}
			}			
		}
		return $result;
	}
	
	/*
	 * Untuk mengecek class $cls sama dengan class $super, atau $cls adalah turunan dari class $super
	 */
	public static function isClassOf($cls, $super) {
		$parent = new ReflectionClass($super);
		return $cls === $super || is_subclass_of($cls, $parent->getName());
	}
}