<?php
namespace Bitrix\Timeman\Model\Monitor;

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\ORM\Data\DataManager,
	Bitrix\Main\ORM\Fields\IntegerField,
	Bitrix\Main\ORM\Fields\StringField,
	Bitrix\Main\ORM\Fields\Validators\LengthValidator;

Loc::loadMessages(__FILE__);

/**
 * Class MonitorEntityTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> TYPE string(100) mandatory
 * <li> TITLE string(2000) mandatory
 * <li> PUBLIC_CODE string(32) optional
 * </ul>
 *
 * @package Bitrix\Timeman\Model\Monitor
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_MonitorEntity_Query query()
 * @method static EO_MonitorEntity_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_MonitorEntity_Result getById($id)
 * @method static EO_MonitorEntity_Result getList(array $parameters = array())
 * @method static EO_MonitorEntity_Entity getEntity()
 * @method static \Bitrix\Timeman\Model\Monitor\EO_MonitorEntity createObject($setDefaultValues = true)
 * @method static \Bitrix\Timeman\Model\Monitor\EO_MonitorEntity_Collection createCollection()
 * @method static \Bitrix\Timeman\Model\Monitor\EO_MonitorEntity wakeUpObject($row)
 * @method static \Bitrix\Timeman\Model\Monitor\EO_MonitorEntity_Collection wakeUpCollection($rows)
 */

class MonitorEntityTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_timeman_monitor_entity';
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
					'title' => Loc::getMessage('MONITOR_ENTITY_ENTITY_ID_FIELD')
				]
			),
			new StringField(
				'TYPE',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validateType'],
					'title' => Loc::getMessage('MONITOR_ENTITY_ENTITY_TYPE_FIELD')
				]
			),
			new StringField(
				'TITLE',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validateTitle'],
					'title' => Loc::getMessage('MONITOR_ENTITY_ENTITY_TITLE_FIELD')
				]
			),
			new StringField(
				'PUBLIC_CODE',
				[
					'validation' => [__CLASS__, 'validatePublicCode'],
					'title' => Loc::getMessage('MONITOR_ENTITY_ENTITY_PUBLIC_CODE_FIELD')
				]
			),
		];
	}

	/**
	 * Returns validators for TYPE field.
	 *
	 * @return array
	 */
	public static function validateType()
	{
		return [
			new LengthValidator(null, 100),
		];
	}

	/**
	 * Returns validators for TITLE field.
	 *
	 * @return array
	 */
	public static function validateTitle()
	{
		return [
			new LengthValidator(null, 2000),
		];
	}

	/**
	 * Returns validators for PUBLIC_CODE field.
	 *
	 * @return array
	 */
	public static function validatePublicCode()
	{
		return [
			new LengthValidator(null, 32),
		];
	}
}