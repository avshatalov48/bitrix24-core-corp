<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * Generic type for a user field
 *
 * @access private
 */

namespace Bitrix\Tasks\Util\UserField;

use Bitrix\Tasks\Util\UserField;

abstract class Type
{
	/**
	 * @param $dataType
	 * @return static string
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getClass($dataType)
	{
		$dataType = trim((string) $dataType);
		if($dataType == '')
		{
			throw new \Bitrix\Main\ArgumentException('$dataType could not be empty');
		}
		$dataType = str_replace('_', '', $dataType);

		$className = __NAMESPACE__.'\\type\\'.$dataType;
		if(!class_exists($className))
		{
			return __CLASS__;
		}

		return $className;
	}

	/**
	 * Clone value of $fromField, converting to a representation of $toField
	 *
	 * @param mixed $value User field value to be cloned
	 * @param mixed[] $entityData Entity data that *may* be modified by this type of user field
	 * @param mixed[] $fromField Source field structure
	 * @param mixed[] $toField Destination field structure
	 * @param int $userId
	 * @param mixed[] $parameters Source field structure
	 * @return array|mixed
	 */
	public static function cloneValue($value, array &$entityData, array $fromField, array $toField, $userId = 0, array $parameters = array())
	{
		return static::translateValueByMultiple($value, $fromField, $toField);
	}

	public static function cancelCloneValue($value, array $fromField, array $toField, $userId = false)
	{
		return ''; // do nothing by default
	}

	public static function getDefaultValueSingle(array $field)
	{
		$default = $field['SETTINGS']['DEFAULT_VALUE'];
		if((string) $default == '')
		{
			return '';
		}

		return $default;
	}

	/**
	 * Translate value from multiple to single, and visa-versa, if needed
	 *
	 * @param $value
	 * @param array $fromField
	 * @param array $toField
	 * @return array|mixed
	 */
	protected static function translateValueByMultiple($value, array $fromField, array $toField)
	{
		if(!UserField::isValueEmpty($value))
		{
			if($fromField['MULTIPLE'] == 'Y' && $toField['MULTIPLE'] != 'Y') // multiple -> single
			{
				if(is_array($value))
				{
					$value = array_shift($value);
				}
			}
			elseif($fromField['MULTIPLE'] != 'Y' && $toField['MULTIPLE'] == 'Y') // single -> multiple
			{
				$value = array($value);
			}
		}

		return $value;
	}
}