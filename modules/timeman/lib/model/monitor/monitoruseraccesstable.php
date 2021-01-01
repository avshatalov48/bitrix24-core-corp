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
 * Class MonitorUserAccessTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> USER_ID int mandatory
 * <li> ENTITY_TYPE string(100) mandatory
 * <li> ENTITY_ID int mandatory
 * <li> DATE_START datetime optional
 * <li> DATE_FINISH datetime optional
 * <li> APPROVED_USER_ID int mandatory
 * <li> DATE_CREATE datetime optional
 * <li> GROUP_CODE string(100) mandatory
 * </ul>
 *
 * @package Bitrix\Timeman\Model\Monitor
 **/

class MonitorUserAccessTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_timeman_monitor_user_access';
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
					'title' => Loc::getMessage('MONITOR_USER_ACCESS_ENTITY_ID_FIELD')
				]
			),
			new IntegerField(
				'USER_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('MONITOR_USER_ACCESS_ENTITY_USER_ID_FIELD')
				]
			),
			new StringField(
				'ENTITY_TYPE',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validateEntityType'],
					'title' => Loc::getMessage('MONITOR_USER_ACCESS_ENTITY_ENTITY_TYPE_FIELD')
				]
			),
			new IntegerField(
				'ENTITY_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('MONITOR_USER_ACCESS_ENTITY_ENTITY_ID_FIELD')
				]
			),
			new DatetimeField(
				'DATE_START',
				[
					'title' => Loc::getMessage('MONITOR_USER_ACCESS_ENTITY_DATE_START_FIELD')
				]
			),
			new DatetimeField(
				'DATE_FINISH',
				[
					'title' => Loc::getMessage('MONITOR_USER_ACCESS_ENTITY_DATE_FINISH_FIELD')
				]
			),
			new IntegerField(
				'APPROVED_USER_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('MONITOR_USER_ACCESS_ENTITY_APPROVED_USER_ID_FIELD')
				]
			),
			new DatetimeField(
				'DATE_CREATE',
				[
					'title' => Loc::getMessage('MONITOR_USER_ACCESS_ENTITY_DATE_CREATE_FIELD')
				]
			),
			new StringField(
				'GROUP_CODE',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validateGroupCode'],
					'title' => Loc::getMessage('MONITOR_USER_ACCESS_ENTITY_GROUP_CODE_FIELD')
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
}