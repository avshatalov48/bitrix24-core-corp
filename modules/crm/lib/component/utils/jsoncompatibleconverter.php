<?php

namespace Bitrix\Crm\Component\Utils;

final class JsonCompatibleConverter
{
	/**
	 * Convert an array to format compatible with CUtil::PhpToJSObject to use in Json::encode
	 */
	public static function convert(array $data, bool $skipConvertIntAndFloat = false): mixed
	{
		return (new self())->doConvert($data, $skipConvertIntAndFloat);
	}

	private function doConvert(mixed $value, bool $skipConvertIntAndFloat): mixed
	{
		if (is_array($value))
		{
			foreach ($value as $key => $subValue)
			{
				$value[$key] = $this->doConvert($subValue, $skipConvertIntAndFloat);
			}
		}
		elseif ($skipConvertIntAndFloat && !is_bool($value))
		{
			if (!is_int($value) && !is_float($value))
			{
				$value = (string)$value;
			}
		}
		elseif (!is_bool($value))
		{
			$value = (string)$value;
		}

		return $value;
	}
}
