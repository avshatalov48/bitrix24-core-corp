<?php
namespace Bitrix\Timeman\Model\Monitor;

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\ORM\Data\DataManager,
	Bitrix\Main\ORM\Fields\DatetimeField,
	Bitrix\Main\ORM\Fields\IntegerField;

Loc::loadMessages(__FILE__);

/**
 * Class MonitorAbsenceTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> USER_LOG_ID int mandatory
 * <li> TIME_START datetime mandatory
 * <li> TIME_FINISH datetime mandatory
 * </ul>
 *
 * @package Bitrix\Timeman\Model\Monitor
 **/

class MonitorAbsenceTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_timeman_monitor_absence';
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
					'title' => Loc::getMessage('MONITOR_ABSENCE_ENTITY_ID_FIELD')
				]
			),
			new IntegerField(
				'USER_LOG_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('MONITOR_ABSENCE_ENTITY_USER_LOG_ID_FIELD')
				]
			),
			new DatetimeField(
				'TIME_START',
				[
					'required' => true,
					'title' => Loc::getMessage('MONITOR_ABSENCE_ENTITY_TIME_START_FIELD')
				]
			),
			new DatetimeField(
				'TIME_FINISH',
				[
					'title' => Loc::getMessage('MONITOR_ABSENCE_ENTITY_TIME_FINISH_FIELD')
				]
			),
		];
	}
}