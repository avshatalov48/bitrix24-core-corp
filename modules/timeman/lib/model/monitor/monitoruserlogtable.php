<?php
namespace Bitrix\Timeman\Model\Monitor;

use Bitrix\Main\Localization\Loc,
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
 * <li> DATE_LOG date mandatory
 * <li> USER_ID int mandatory
 * <li> DESKTOP_CODE string(32) mandatory
 * <li> CODE string(32) mandatory
 * <li> APP_CODE string(32) mandatory
 * <li> SITE_CODE string(32) optional
 * <li> TIME_SPEND int optional default 0
 * </ul>
 *
 * @package Bitrix\Timeman\Model\Monitor
 **/

class MonitorUserLogTable extends Base
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
			new DateField(
				'DATE_LOG',
				[
					'primary' => true,
					'title' => Loc::getMessage('MONITOR_USER_LOG_ENTITY_DATE_LOG_FIELD')
				]
			),
			new IntegerField(
				'USER_ID',
				[
					'primary' => true,
					'title' => Loc::getMessage('MONITOR_USER_LOG_ENTITY_USER_ID_FIELD')
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
			new StringField(
				'CODE',
				[
					'primary' => true,
					'validation' => [__CLASS__, 'validateCode'],
					'title' => Loc::getMessage('MONITOR_USER_LOG_ENTITY_CODE_FIELD')
				]
			),
			new StringField(
				'APP_CODE',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validateAppCode'],
					'title' => Loc::getMessage('MONITOR_USER_LOG_ENTITY_APP_CODE_FIELD')
				]
			),
			new StringField(
				'SITE_CODE',
				[
					'validation' => [__CLASS__, 'validateSiteCode'],
					'title' => Loc::getMessage('MONITOR_USER_LOG_ENTITY_SITE_CODE_FIELD')
				]
			),
			new IntegerField(
				'TIME_SPEND',
				[
					'default' => 0,
					'title' => Loc::getMessage('MONITOR_USER_LOG_ENTITY_TIME_SPEND_FIELD')
				]
			),
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

	/**
	 * Returns validators for CODE field.
	 *
	 * @return array
	 */
	public static function validateCode()
	{
		return [
			new LengthValidator(null, 32),
		];
	}

	/**
	 * Returns validators for APP_CODE field.
	 *
	 * @return array
	 */
	public static function validateAppCode()
	{
		return [
			new LengthValidator(null, 32),
		];
	}

	/**
	 * Returns validators for SITE_CODE field.
	 *
	 * @return array
	 */
	public static function validateSiteCode()
	{
		return [
			new LengthValidator(null, 32),
		];
	}

	protected static function getMergeFields()
	{
		return ['DATE_LOG', 'USER_ID', 'CODE'];
	}
}