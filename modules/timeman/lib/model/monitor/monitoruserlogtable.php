<?php
namespace Bitrix\Timeman\Model\Monitor;

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\ORM\Data\DataManager,
	Bitrix\Main\ORM\Fields\DateField,
	Bitrix\Main\ORM\Fields\IntegerField,
	Bitrix\Main\ORM\Fields\StringField,
	Bitrix\Main\ORM\Fields\Validators\LengthValidator;

Loc::loadMessages(__FILE__);

/**
 * Class MonitorUserLogTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> DATE_LOG date mandatory
 * <li> USER_ID int mandatory
 * <li> PRIVATE_CODE string(40) mandatory
 * <li> ENTITY_ID int mandatory
 * <li> TIME_SPEND int optional default 0
 * <li> DESKTOP_CODE string(32) mandatory
 * </ul>
 *
 * @package Bitrix\Timeman\Model\Monitor
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_MonitorUserLog_Query query()
 * @method static EO_MonitorUserLog_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_MonitorUserLog_Result getById($id)
 * @method static EO_MonitorUserLog_Result getList(array $parameters = array())
 * @method static EO_MonitorUserLog_Entity getEntity()
 * @method static \Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog createObject($setDefaultValues = true)
 * @method static \Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog_Collection createCollection()
 * @method static \Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog wakeUpObject($row)
 * @method static \Bitrix\Timeman\Model\Monitor\EO_MonitorUserLog_Collection wakeUpCollection($rows)
 */

class MonitorUserLogTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_timeman_monitor_user_log';
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
					'title' => Loc::getMessage('MONITOR_USER_LOG_ENTITY_ID_FIELD')
				]
			),
			new DateField(
				'DATE_LOG',
				[
					'required' => true,
					'title' => Loc::getMessage('MONITOR_USER_LOG_ENTITY_DATE_LOG_FIELD')
				]
			),
			new IntegerField(
				'USER_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('MONITOR_USER_LOG_ENTITY_USER_ID_FIELD')
				]
			),
			new StringField(
				'PRIVATE_CODE',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validatePrivateCode'],
					'title' => Loc::getMessage('MONITOR_USER_LOG_ENTITY_PRIVATE_CODE_FIELD')
				]
			),
			new IntegerField(
				'ENTITY_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('MONITOR_USER_LOG_ENTITY_ENTITY_ID_FIELD')
				]
			),
			new IntegerField(
				'TIME_SPEND',
				[
					'default' => 0,
					'title' => Loc::getMessage('MONITOR_USER_LOG_ENTITY_TIME_SPEND_FIELD')
				]
			),
			new StringField(
				'DESKTOP_CODE',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validateDesktopCode'],
					'title' => Loc::getMessage('MONITOR_USER_LOG_ENTITY_DESKTOP_CODE_FIELD')
				]
			),
		];
	}

	/**
	 * Returns validators for PRIVATE_CODE field.
	 *
	 * @return array
	 */
	public static function validatePrivateCode()
	{
		return [
			new LengthValidator(null, 40),
		];
	}

	/**
	 * Returns validators for DESKTOP_CODE field.
	 *
	 * @return array
	 */
	public static function validateDesktopCode()
	{
		return [
			new LengthValidator(null, 32),
		];
	}
}