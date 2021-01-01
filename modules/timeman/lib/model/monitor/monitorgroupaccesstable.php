<?php
namespace Bitrix\Timeman\Model\Monitor;

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\ORM\Data\DataManager,
	Bitrix\Main\ORM\Fields\DatetimeField,
	Bitrix\Main\ORM\Fields\IntegerField,
	Bitrix\Main\ORM\Fields\StringField,
	Bitrix\Main\ORM\Fields\Validators\LengthValidator;

Loc::loadMessages(__FILE__);

/**
 * Class MonitorGroupAccessTable
 *
 * Fields:
 * <ul>
 * <li> DEPARTMENT_ID int optional default 0
 * <li> ENTITY_TYPE string(100) mandatory
 * <li> ENTITY_ID int mandatory
 * <li> GROUP_CODE string(100) mandatory
 * <li> CREATED_USER_ID int mandatory
 * <li> DATE_CREATE datetime optional
 * </ul>
 *
 * @package Bitrix\Timeman\Model\Monitor
 **/

class MonitorGroupAccessTable extends Base
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_timeman_monitor_group_access';
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
				'DEPARTMENT_ID',
				[
					'primary' => true,
					'default' => 0,
					'title' => Loc::getMessage('MONITOR_GROUP_ACCESS_ENTITY_DEPARTMENT_ID_FIELD')
				]
			),
			new StringField(
				'ENTITY_TYPE',
				[
					'primary' => true,
					'validation' => [__CLASS__, 'validateEntityType'],
					'title' => Loc::getMessage('MONITOR_GROUP_ACCESS_ENTITY_ENTITY_TYPE_FIELD')
				]
			),
			new IntegerField(
				'ENTITY_ID',
				[
					'primary' => true,
					'title' => Loc::getMessage('MONITOR_GROUP_ACCESS_ENTITY_ENTITY_ID_FIELD')
				]
			),
			new StringField(
				'GROUP_CODE',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validateGroupCode'],
					'title' => Loc::getMessage('MONITOR_GROUP_ACCESS_ENTITY_GROUP_CODE_FIELD')
				]
			),
			new IntegerField(
				'CREATED_USER_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('MONITOR_GROUP_ACCESS_ENTITY_CREATED_USER_ID_FIELD')
				]
			),
			new DatetimeField(
				'DATE_CREATE',
				[
					'title' => Loc::getMessage('MONITOR_GROUP_ACCESS_ENTITY_DATE_CREATE_FIELD')
				]
			),
		];
	}

	/**
	 * Returns validators for ENTITY_TYPE field.
	 *
	 * @return array
	 */
	public static function validateEntityType()
	{
		return [
			new LengthValidator(null, 100),
		];
	}

	/**
	 * Returns validators for GROUP_CODE field.
	 *
	 * @return array
	 */
	public static function validateGroupCode()
	{
		return [
			new LengthValidator(null, 100),
		];
	}

	protected static function getMergeFields()
	{
		return ['ENTITY_TYPE', 'ENTITY_ID', 'DEPARTMENT_ID'];
	}
}