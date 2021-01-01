<?php
namespace Bitrix\Timeman\Model\Monitor;

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\ORM\Fields\IntegerField,
	Bitrix\Main\ORM\Fields\StringField,
	Bitrix\Main\ORM\Fields\Validators\LengthValidator;

Loc::loadMessages(__FILE__);

/**
 * Class MonitorSiteTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> CODE string(32) mandatory
 * <li> NAME string(2000) mandatory
 * <li> HOST string(2000) mandatory
 * </ul>
 *
 * @package Bitrix\Timeman\Model\Monitor
 **/

class MonitorSiteTable extends Base
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_timeman_monitor_site';
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
					'title' => Loc::getMessage('MONITOR_SITE_ENTITY_ID_FIELD')
				]
			),
			new StringField(
				'CODE',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validateCode'],
					'title' => Loc::getMessage('MONITOR_SITE_ENTITY_CODE_FIELD')
				]
			),
			new StringField(
				'NAME',
				[
					'validation' => [__CLASS__, 'validateName'],
					'title' => Loc::getMessage('MONITOR_SITE_ENTITY_NAME_FIELD')
				]
			),
			new StringField(
				'HOST',
				[
					'validation' => [__CLASS__, 'validateHost'],
					'title' => Loc::getMessage('MONITOR_SITE_ENTITY_HOST_FIELD')
				]
			),
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
	 * Returns validators for NAME field.
	 *
	 * @return array
	 */
	public static function validateName()
	{
		return [
			new LengthValidator(null, 2000),
		];
	}

	/**
	 * Returns validators for HOST field.
	 *
	 * @return array
	 */
	public static function validateHost()
	{
		return [
			new LengthValidator(null, 2000),
		];
	}
}