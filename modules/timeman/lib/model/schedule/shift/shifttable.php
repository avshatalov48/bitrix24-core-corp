<?php
namespace Bitrix\Timeman\Model\Schedule\Shift;

use \Bitrix\Main;
use \Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Timeman\Model\Schedule\ScheduleTable;

Loc::loadMessages(__FILE__);

/**
 * Class ShiftTable
 * @package Bitrix\Timeman\Model\Schedule\Shift
 */
class ShiftTable extends Main\ORM\Data\DataManager
{
	const DELETED_YES = 1;
	const DELETED_NO = 0;

	public static function getObjectClass()
	{
		return Shift::class;
	}

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_timeman_work_shift';
	}

	public static function getWorkdaysOptions()
	{
		return [
			'12345' => Loc::getMessage('TM_WORKSHIFT_FORM_WORKDAYS_OPTION_MON_FRI'),
			'123456' => Loc::getMessage('TM_WORKSHIFT_FORM_WORKDAYS_OPTION_MON_SAT'),
			'' => Loc::getMessage('TM_WORKSHIFT_FORM_WORKDAYS_OPTION_CUSTOM'),
		];
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			(new Fields\IntegerField('ID'))
				->configurePrimary(true)
				->configureAutocomplete(true)
			,
			(new Fields\StringField('NAME'))
				->configureRequired(false)
				->configureDefaultValue(function () {
					return '';
				})
			,
			(new Fields\IntegerField('BREAK_DURATION'))
				->configureDefaultValue(function () {
					return 0;
				})
			,
			(new Fields\IntegerField('WORK_TIME_START'))
				->configureDefaultValue(function () {
					return 0;
				})
			,
			(new Fields\IntegerField('WORK_TIME_END'))
				->configureDefaultValue(function () {
					return 0;
				})
			,
			(new Fields\StringField('WORK_DAYS'))
				->configureDefaultValue(function () {
					return '';
				})
			,
			(new Fields\IntegerField('SCHEDULE_ID'))
				->configureRequired(true)
			,
			(new Fields\IntegerField('DELETED'))
				->configureDefaultValue(function () {
					return 0;
				})
			,
			# relations
			(new Fields\Relations\Reference(
				'SCHEDULE',
				ScheduleTable::class,
				Join::on('this.SCHEDULE_ID', 'ref.ID')->where('this.DELETED', static::DELETED_NO)
			))
				->configureJoinType('INNER')
			,
		];
	}
}