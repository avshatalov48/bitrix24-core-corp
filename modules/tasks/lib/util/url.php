<?php

namespace Bitrix\Tasks\Util;

class Url
{
	public static function AddUrlParams($url, $params)
	{
		if(empty($params))
		{
			return $url;
		}

		$query = array();
		foreach($params as $k => $v)
		{
			self::AppendUrlParam($k, $v, $query);
		}

		return $url.(mb_strpos($url, '?') === false ? '?' : '&').implode('&', $query);
	}

	private static function AppendUrlParam($name, $value, array &$params)
	{
		if(!is_array($value))
		{
			$params[] = $name.'='.$value;
		}
		else
		{
			foreach($value as $v)
			{
				self::AppendUrlParam("{$name}[]", $v, $params);
			}
		}
	}
}