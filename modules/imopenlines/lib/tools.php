<?php
namespace Bitrix\ImOpenLines;

class Tools
{
	/**
	 * The analogue of the function empty, which 0, 0.0 and "0" is not considered an empty value.
	 *
	 * @param $value
	 * @return bool
	 */
	public static function isEmpty($value): bool
	{
		if(empty($value))
		{
			if(
				isset($value) &&
				(
					$value === 0 ||
					$value === 0.0 ||
					$value === '0'
				)
			)
			{
				$result = false;
			}
			else
			{
				$result = true;
			}
		}
		else
		{
			$result = false;
		}

		return $result;
	}
}