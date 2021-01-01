<?php
namespace Bitrix\Timeman\Model\Monitor;

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\ORM\Data\DataManager,
	Bitrix\Main\ORM\Fields\BooleanField,
	Bitrix\Main\ORM\Fields\IntegerField,
	Bitrix\Main\ORM\Fields\StringField,
	Bitrix\Main\ORM\Fields\TextField,
	Bitrix\Main\ORM\Fields\Validators\LengthValidator;

Loc::loadMessages(__FILE__);

/**
 * Class MonitorGroupTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> NAME text mandatory
 * <li> COLOR string(7) optional
 * <li> DEFAULT_GROUP bool ('N', 'Y') optional default 'N'
 * <li> HIDDEN bool ('N', 'Y') optional default 'N'
 * <li> CODE string(100) mandatory
 * </ul>
 *
 * @package Bitrix\Timeman\Model\Monitor
 **/

class MonitorGroupTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_timeman_monitor_group';
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
					'title' => Loc::getMessage('MONITOR_GROUP_ENTITY_ID_FIELD')
				]
			),
			new TextField(
				'NAME',
				[
					'required' => true,
					'title' => Loc::getMessage('MONITOR_GROUP_ENTITY_NAME_FIELD')
				]
			),
			new StringField(
				'COLOR',
				[
					'validation' => [__CLASS__, 'validateColor'],
					'title' => Loc::getMessage('MONITOR_GROUP_ENTITY_COLOR_FIELD')
				]
			),
			new BooleanField(
				'DEFAULT_GROUP',
				[
					'values' => array('N', 'Y'),
					'default' => 'N',
					'title' => Loc::getMessage('MONITOR_GROUP_ENTITY_DEFAULT_GROUP_FIELD')
				]
			),
			new BooleanField(
				'HIDDEN',
				[
					'values' => array('N', 'Y'),
					'default' => 'N',
					'title' => Loc::getMessage('MONITOR_GROUP_ENTITY_HIDDEN_FIELD')
				]
			),
			new StringField(
				'CODE',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validateCode'],
					'title' => Loc::getMessage('MONITOR_GROUP_ENTITY_CODE_FIELD')
				]
			),
		];
	}

	/**
	 * Returns validators for COLOR field.
	 *
	 * @return array
	 */
	public static function validateColor()
	{
		return [
			new LengthValidator(null, 7),
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
			new LengthValidator(null, 100),
		];
	}
}