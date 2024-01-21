<?php

namespace Bitrix\Calendar\Internals;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

/**
 * Class SharingObjectRuleTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> OBJECT_ID int mandatory
 * <li> OBJECT_TYPE string(32) mandatory
 * <li> SLOT_SIZE int mandatory
 * <li> WEEKDAYS string(32) optional
 * <li> TIME_FROM int optional
 * <li> TIME_TO int optional
 * </ul>
 *
 * @package Bitrix\Calendar
 **/

class SharingObjectRuleTable extends DataManager
{
	use DeleteByFilterTrait;

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_calendar_sharing_object_rule';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			new IntegerField(
				'ID',
				[
					'primary' => true,
					'autocomplete' => true,
				]
			),
			new IntegerField(
				'OBJECT_ID',
				[
					'required' => true,
				]
			),
			new StringField(
				'OBJECT_TYPE',
				[
					'required' => true,
					'validation' => function()
					{
						return[
							new LengthValidator(null, 32),
						];
					},
				]
			),
			new IntegerField(
				'SLOT_SIZE',
				[
					'required' => true,
				]
			),
			new StringField(
				'WEEKDAYS',
				[
					'validation' => function()
					{
						return[
							new LengthValidator(null, 32),
						];
					},
				]
			),
			new IntegerField('TIME_FROM'),
			new IntegerField('TIME_TO'),
		];
	}
}