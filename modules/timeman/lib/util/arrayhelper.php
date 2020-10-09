<?php
namespace Bitrix\Timeman\Util;

class ArrayHelper
{

	public static function getValue($array, $key, $default = null)
	{
		if ($key instanceof \Closure)
		{
			return $key($array, $default);
		}

		if (is_array($key))
		{
			$lastKey = array_pop($key);
			foreach ($key as $keyPart)
			{
				$array = static::getValue($array, $keyPart);
			}
			$key = $lastKey;
		}

		if (is_array($array) && (isset($array[$key]) || array_key_exists($key, $array)))
		{
			return $array[$key];
		}

		if (($pos = mb_strrpos($key, '.')) !== false)
		{
			$array = static::getValue($array, mb_substr($key, 0, $pos), $default);
			$key = mb_substr($key, $pos + 1);
		}

		if (is_object($array))
		{
			return $array->$key;
		}
		elseif (is_array($array))
		{
			return (isset($array[$key]) || array_key_exists($key, $array)) ? $array[$key] : $default;
		}
		else
		{
			return $default;
		}
	}

}