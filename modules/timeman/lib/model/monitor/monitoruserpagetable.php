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
 * Class MonitorUserPageTable
 *
 * Fields:
 * <ul>
 * <li> DATE_LOG date mandatory
 * <li> USER_ID int mandatory
 * <li> DESKTOP_CODE string(32) mandatory
 * <li> CODE string(32) mandatory
 * <li> PAGE_CODE string(32) mandatory
 * <li> SITE_CODE string(32) optional
 * <li> SITE_URL string(2000) optional
 * <li> SITE_TITLE string(2000) optional
 * <li> TIME_SPEND int optional default 0
 * </ul>
 *
 * @package Bitrix\Timeman\Model\Monitor
 **/

class MonitorUserPageTable extends Base
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_timeman_monitor_user_page';
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
					'title' => Loc::getMessage('MONITOR_USER_PAGE_ENTITY_DATE_LOG_FIELD')
				]
			),
			new IntegerField(
				'USER_ID',
				[
					'primary' => true,
					'title' => Loc::getMessage('MONITOR_USER_PAGE_ENTITY_USER_ID_FIELD')
				]
			),
			new StringField(
				'DESKTOP_CODE',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validateDesktopCode'],
					'title' => Loc::getMessage('MONITOR_USER_PAGE_ENTITY_DESKTOP_CODE_FIELD')
				]
			),
			new StringField(
				'CODE',
				[
					'primary' => true,
					'validation' => [__CLASS__, 'validateCode'],
					'title' => Loc::getMessage('MONITOR_USER_PAGE_ENTITY_CODE_FIELD')
				]
			),
			new StringField(
				'PAGE_CODE',
				[
					'primary' => true,
					'validation' => [__CLASS__, 'validatePageCode'],
					'title' => Loc::getMessage('MONITOR_USER_PAGE_ENTITY_PAGE_CODE_FIELD')
				]
			),
			new StringField(
				'SITE_CODE',
				[
					'validation' => [__CLASS__, 'validateSiteCode'],
					'title' => Loc::getMessage('MONITOR_USER_PAGE_ENTITY_SITE_CODE_FIELD')
				]
			),
			new StringField(
				'SITE_URL',
				[
					'validation' => [__CLASS__, 'validateSiteUrl'],
					'title' => Loc::getMessage('MONITOR_USER_PAGE_ENTITY_SITE_URL_FIELD')
				]
			),
			new StringField(
				'SITE_TITLE',
				[
					'validation' => [__CLASS__, 'validateSiteTitle'],
					'title' => Loc::getMessage('MONITOR_USER_PAGE_ENTITY_SITE_TITLE_FIELD')
				]
			),
			new IntegerField(
				'TIME_SPEND',
				[
					'default' => 0,
					'title' => Loc::getMessage('MONITOR_USER_PAGE_ENTITY_TIME_SPEND_FIELD')
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
	 * Returns validators for PAGE_CODE field.
	 *
	 * @return array
	 */
	public static function validatePageCode()
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

	/**
	 * Returns validators for SITE_URL field.
	 *
	 * @return array
	 */
	public static function validateSiteUrl()
	{
		return [
			new LengthValidator(null, 2000),
		];
	}

	/**
	 * Returns validators for SITE_TITLE field.
	 *
	 * @return array
	 */
	public static function validateSiteTitle()
	{
		return [
			new LengthValidator(null, 2000),
		];
	}

	protected static function getMergeFields()
	{
		return ['DATE_LOG', 'USER_ID', 'CODE', 'PAGE_CODE'];
	}
}