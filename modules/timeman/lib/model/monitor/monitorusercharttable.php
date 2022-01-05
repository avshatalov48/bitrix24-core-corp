<?php
namespace Bitrix\Timeman\Model\Monitor;

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\ORM\Data\DataManager,
	Bitrix\Main\ORM\Fields\DateField,
	Bitrix\Main\ORM\Fields\DatetimeField,
	Bitrix\Main\ORM\Fields\IntegerField,
	Bitrix\Main\ORM\Fields\StringField,
	Bitrix\Main\ORM\Fields\Validators\LengthValidator;

Loc::loadMessages(__FILE__);

/**
 * Class MonitorUserChartTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> DATE_LOG date mandatory
 * <li> USER_ID int mandatory
 * <li> DESKTOP_CODE string(32) mandatory
 * <li> GROUP_TYPE string(100) mandatory
 * <li> TIME_START datetime mandatory
 * <li> TIME_FINISH datetime mandatory
 * </ul>
 *
 * @package Bitrix\Timeman\Model\Monitor
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_MonitorUserChart_Query query()
 * @method static EO_MonitorUserChart_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_MonitorUserChart_Result getById($id)
 * @method static EO_MonitorUserChart_Result getList(array $parameters = array())
 * @method static EO_MonitorUserChart_Entity getEntity()
 * @method static \Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart createObject($setDefaultValues = true)
 * @method static \Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart_Collection createCollection()
 * @method static \Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart wakeUpObject($row)
 * @method static \Bitrix\Timeman\Model\Monitor\EO_MonitorUserChart_Collection wakeUpCollection($rows)
 */

class MonitorUserChartTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_timeman_monitor_user_chart';
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
					'title' => Loc::getMessage('MONITOR_USER_CHART_ENTITY_ID_FIELD')
				]
			),
			new DateField(
				'DATE_LOG',
				[
					'required' => true,
					'title' => Loc::getMessage('MONITOR_USER_CHART_ENTITY_DATE_LOG_FIELD')
				]
			),
			new IntegerField(
				'USER_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('MONITOR_USER_CHART_ENTITY_USER_ID_FIELD')
				]
			),
			new StringField(
				'DESKTOP_CODE',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validateDesktopCode'],
					'title' => Loc::getMessage('MONITOR_USER_CHART_ENTITY_DESKTOP_CODE_FIELD')
				]
			),
			new StringField(
				'GROUP_TYPE',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validateGroupType'],
					'title' => Loc::getMessage('MONITOR_USER_CHART_ENTITY_GROUP_TYPE_FIELD')
				]
			),
			new DatetimeField(
				'TIME_START',
				[
					'required' => true,
					'title' => Loc::getMessage('MONITOR_USER_CHART_ENTITY_TIME_START_FIELD')
				]
			),
			new DatetimeField(
				'TIME_FINISH',
				[
					'required' => true,
					'title' => Loc::getMessage('MONITOR_USER_CHART_ENTITY_TIME_FINISH_FIELD')
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
	 * Returns validators for GROUP_TYPE field.
	 *
	 * @return array
	 */
	public static function validateGroupType()
	{
		return [
			new LengthValidator(null, 100),
		];
	}
}