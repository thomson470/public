<?php
namespace App\com\sprint\sms\api\util;

final class StringUtil
{
	public static function startsWith($str, $chr)
	{
		 $length = strlen($chr);
		 return (substr($str, 0, $length) === $chr);
	}

	public static function endsWith($str, $chr)
	{
		$length = strlen($chr);
		if ($length == 0) {
			return true;
		}

		return (substr($str, -$length) === $chr);
	}
}