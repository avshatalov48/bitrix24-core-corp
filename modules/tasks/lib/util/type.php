<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @access private
 */

namespace Bitrix\Tasks\Util;

abstract class Type
{
	public static function isIterable($arg)
	{
		if(is_array($arg))
		{
			return true;
		}

		if(is_object($arg))
		{
			$iFaces = class_implements($arg);
			return isset($iFaces['Iterator']) || isset($iFaces['IteratorAggregate']);
		}

		return false;
	}

	public static function convertBooleanUserFieldValue($value)
	{
		if (mb_strtolower($value) == 'n')
		{
			return false;
		}
		elseif ($value)
		{
			return true;
		}

		return false;
	}

	/////////////////////////////////
	// helper functions for checking elements of component parameters and other similar places

	public static function checkYNKey(array &$data, $paramName)
	{
		if((string) $paramName != '' && array_key_exists($paramName, $data))
		{
			$data[$paramName] = $data[$paramName] == 'Y' ? 'Y' : 'N';
		}
	}

	public static function checkBooleanKey(array &$data, $paramName, $default = null)
	{
		if((string) $paramName != '' && array_key_exists($paramName, $data))
		{
			if($data[$paramName] != 'Y' && $data[$paramName] != 'N' && $default !== null)
			{
				$data[$paramName] = $default;
			}
			else
			{
				$data[$paramName] = $data[$paramName] == 'Y';
			}
		}
	}

	public static function checkEnumKey(array &$data, $paramName, array $enum, $default = null)
	{
		if((string) $paramName != '' && array_key_exists($paramName, $data))
		{
			$enum = array_flip($enum);

			if(!isset($enum[$data[$paramName]]))
			{
				if($default !== null)
				{
					$data[$paramName] = $default;
				}
				else
				{
					unset($data[$paramName]);
					return false; // value was incorrect
				}
			}
		}

		return true; // value was correct or was replaced with the default one (which is assumed to be correct)
	}

	public static function checkArrayOfUPIntegerKey(&$data, $paramName)
	{
		if((array) $data !== $data)
		{
			$data = array();
		}

		if((string) $paramName != '' && array_key_exists($paramName, $data))
		{
			$data[$paramName] = static::castToArrayOfUniquePositiveInteger($data[$paramName]);
		}
	}

	private static function castToArrayOfUniquePositiveInteger($arg)
	{
		if(isset($arg))
		{
			if(!is_array($arg))
			{
				$arg = array();
			}
			else
			{
				foreach($arg as $i => &$item)
				{
					$item = intval($item);
					if($item <= 0)
					{
						unset($arg[$i]);
					}
				}
				unset($item);

				$arg = array_unique($arg);
			}
		}

		return $arg;
	}

	public static function serializeArray($data, $returnFalse = false)
	{
		if(!is_array($data))
		{
			$data = $returnFalse ? false : array();
		}

		return serialize($data);
	}

	public static function unSerializeArray($data)
	{
		if (
			!isset($data)
			|| !\CheckSerializedData($data)
		)
		{
			return [];
		}

		$data = unserialize($data, ['allowed_classes' => false]);

		return (is_array($data) ? $data : []);
	}

	/**
	 * Normalizes array of not empty strings passed
	 *
	 * @param $data
	 * @return array
	 */
	public static function normalizeArray($data)
	{
		if(!is_array($data))
		{
			return array();
		}

		foreach($data as $i => $value)
		{
            if (!is_scalar($value))
            {
                continue;
            }
			if((string) $value == '')
			{
				unset($data[$i]);
			}
		}

		return $data;
	}

	/**
	 * Normalizes array of positive IDs passed
	 *
	 * @param $data
	 * @return array
	 */
	public static function normalizeArrayOfUInteger($data)
	{
		if(!is_array($data))
		{
			return array();
		}

		return array_unique(array_filter($data, 'intval'));
	}

	/**
	 * @param $arg
	 * @return bool|int
	 *
	 * @deprecated
	 */
	public static function checkDateTimeString($arg)
	{
		return \Bitrix\Tasks\UI::checkDateTime($arg);
	}
}