<?php
namespace Bitrix\Tasks\Util\UserField\Type;

use Bitrix\Tasks\Util\Collection;
use Bitrix\Tasks\Util\UserField\Type;

/**
 * Class Enumeration
 *
 * @package Bitrix\Tasks\Util\UserField\Type
 */
final class Enumeration extends Type
{
	private static $enums = [];

	public static function cloneValue(
		$value,
		array &$entityData,
		array $fromField,
		array $toField,
		$userId = false,
		array $parameters = []
	)
	{
		$newValue = '';

		if (($isCollection = Collection::isA($value)) || (string)$value != '')
		{
			$newValue = $value;
			$newValue = ($isCollection ? $newValue->export() : $newValue);

			$fromEnum = static::getEnum($fromField['ID']);
			$toEnum = static::getEnum($toField['ID']);

			// create mapping from $fromEnum to $toEnum
			$eMap = [];
			foreach ($fromEnum as $xml => $eData)
			{
				if (is_array($toEnum[$xml]))
				{
					$eMap[$eData['ID']] = $toEnum[$xml]['ID'];
				}
			}

			// now map values
			if ($fromField['MULTIPLE'] === 'Y')
			{
				if (is_array($newValue))
				{
					$mappedValue = [];
					foreach ($newValue as $eValue)
					{
						$mappedValue[] = $eMap[$eValue];
					}
					$newValue = $mappedValue;
				}
			}
			else
			{
				$newValue = $eMap[$newValue];
			}

			$newValue = ($isCollection ? new Collection($newValue) : $newValue);
		}

		return static::translateValueByMultiple($newValue, $fromField, $toField);
	}

	/**
	 * @param $id
	 * @return mixed
	 */
	private static function getEnum($id)
	{
		if (static::$enums[$id] == null)
		{
			static::$enums[$id] = [];

			$i = 0;
			$res = (new \CUserFieldEnum())->GetList([], ['USER_FIELD_ID' => $id]);
			while ($item = $res->Fetch())
			{
				if ((string)$item['XML_ID'] == '')
				{
					$item['XML_ID'] = 'id_'.$i++;
				}

				static::$enums[$id][mb_strtolower(trim($item['XML_ID']))] = $item;
			}
		}

		return static::$enums[$id];
	}
}