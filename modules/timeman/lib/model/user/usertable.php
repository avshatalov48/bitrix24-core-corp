<?php
namespace Bitrix\Timeman\Model\User;

use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Fields\Field;

/**
 * Class UserTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_User_Query query()
 * @method static EO_User_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_User_Result getById($id)
 * @method static EO_User_Result getList(array $parameters = array())
 * @method static EO_User_Entity getEntity()
 * @method static \Bitrix\Timeman\Model\User\User createObject($setDefaultValues = true)
 * @method static \Bitrix\Timeman\Model\User\UserCollection createCollection()
 * @method static \Bitrix\Timeman\Model\User\User wakeUpObject($row)
 * @method static \Bitrix\Timeman\Model\User\UserCollection wakeUpCollection($rows)
 */
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