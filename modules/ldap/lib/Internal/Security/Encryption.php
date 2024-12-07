<?php

namespace Bitrix\Ldap\Internal\Security;

/**
 * @package Bitrix\Ldap\Internal
 * You must not use classes from Internal namespace outside current module.
 */
final class Encryption
{
	public static function encrypt(string $str, ?string $salt = null): string
	{
		$key = $salt ?? \COption::GetOptionString('main', 'pwdhashadd', 'ldap');
		$key1 = self::binMd5($key);
		$res = '';
		while ($str)
		{
			$m = substr($str, 0, 16);
			$str = substr($str, 16, strlen($str) - 16);
			$res .= self::byteXor($m, $key1, 16);
			$key1 = self::binMd5($key . $key1 . $m);
		}
		return base64_encode($res);
	}

	public static function decrypt(string $str, ?string $salt = null): string
	{
		$key = $salt ?? \COption::GetOptionString('main', 'pwdhashadd', 'ldap');
		$key1 = self::binMd5($key);
		$str = base64_decode($str);
		$res = '';
		while ($str)
		{
			$m = substr($str, 0, 16);
			$str = substr($str, 16, strlen($str) - 16);

			$m = self::byteXor($m, $key1, 16);
			$res .= $m;
			$key1 = self::binMd5($key . $key1 . $m);
		}
		return $res;
	}

	/**
	 * @param string $a
	 * @param string $b
	 * @param int $l
	 * @return string
	 */
	public static function byteXor($a, $b, $l)
	{
		$c = '';
		for ($i = 0; $i < $l; $i++)
		{
			$c .= $a[$i] ^ $b[$i];
		}
		return $c;
	}

	/**
	 * @param string $val
	 * @return string
	 */
	public static function binMd5($val)
	{
		return pack('H*', md5($val));
	}
}
