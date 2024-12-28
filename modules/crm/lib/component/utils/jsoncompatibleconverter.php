<?php

namespace Bitrix\Crm\Component\Utils;

class JsonCompatibleConverter
{
	/**
	 * Convert an array to format compatible with CUtil::PhpToJSObject to use in Json::encode
	 *
	 * @param array $data
	 * @return array
	 */
	public static function convert(array $data): array
	{
		$instance = new self();

		return $instance->doConvert($data);
	}

	private function doConvert(mixed $value): mixed
	{
		if (is_array($value))
		{
			foreach ($value as $key => $subValue)
			{
				$value[$key] = $this->doConvert($subValue);
			}
		}
		elseif (!is_bool($value))
		{
			$value = (string)$value;
		}

		return $value;
	}
}
