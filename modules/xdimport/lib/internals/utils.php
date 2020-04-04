<?php

namespace Bitrix\XDImport\Internals;


final class Utils
{

	/**
	 * Returns underialized sonet log params
	 *
	 * @return array
	 */
	public static function getParamsFromString($str)
	{
		$params = unserialize($str);

		if (
			!is_array($params)
			&& !empty($str)
		)
		{
			$tmp = explode("&", $str);
			if (is_array($tmp) && count($tmp) > 0)
			{
				$params = array();
				foreach($tmp as $pair)
				{
					list ($key, $value) = explode("=", $pair);
					$params[$key] = $value;
				}
			}
		}

		return $params;
	}
}
