<?php
namespace Bitrix\Timeman\Model\User;

use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Fields\Field;

class UserTable extends \Bitrix\Intranet\UserTable
{
	public static function getObjectClass()
	{
		return User::class;
	}

	public static function getCollectionClass()
	{
		return UserCollection::class;
	}

	public static function getMap()
	{
		$map = parent::getMap();
		$extraFields = [
			'AUTO_TIME_ZONE' => 'AUTO_TIME_ZONE',
			'TIME_ZONE' => 'TIME_ZONE',
		];

		foreach ($map as $fieldIndex => $field)
		{
			if ($field instanceof Field)
			{
				if (in_array($field->getName(), array_keys($extraFields), true))
				{
					unset($extraFields[$field->getName()]);
				}
			}
			elseif (is_array($field) && in_array($fieldIndex, array_keys($extraFields), true))
			{
				unset($extraFields[$fieldIndex]);
			}
		}
		foreach ($extraFields as $fieldName => $extraField)
		{
			$map[$fieldName] = (new Fields\StringField($fieldName));
		}

		return $map;
	}
}