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
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Shift_Query query()
 * @method static EO_Shift_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Shift_Result getById($id)
 * @method static EO_Shift_Result getList(array $parameters = array())
 * @method static EO_Shift_Entity getEntity()
 * @method static \Bitrix\Timeman\Model\Schedule\Shift\Shift createObject($setDefaultValues = true)
 * @method static \Bitrix\Timeman\Model\Schedule\Shift\ShiftCollection createCollection()
 * @method static \Bitrix\Timeman\Model\Schedule\Shift\Shift wakeUpObject($row)
 * @method static \Bitrix\Timeman\Model\Schedule\Shift\ShiftCollection wakeUpCollection($rows)
 */
class ShiftTable extends Main\ORM\Data\DataManager
{
	const DELETED_YES = 1;
	const DELETED_NO = 0;

	public static function getObjectClass()
	{
		return Shift::class;
	}

	public static function getCollectionClass()
	{
		return ShiftCollection::class;
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
			,
			(new Fields\BooleanField('DELETED'))
				->configureValues(static::DELETED_NO, static::DELETED_YES)
				->configureDefaultValue(false)
			,
			# relations
			(new Fields\Relations\Reference(
				'SCHEDULE',
				ScheduleTable::class,
				Join::on('this.SCHEDULE_ID', 'ref.ID')->where('this.DELETED', static::DELETED_NO)
			))
				->configureJoinType('INNER')
			,
			(new Fields\Relations\Reference(
				'SCHEDULE_WITH_ALL_SHIFTS',
				ScheduleTable::class,
				Join::on('this.SCHEDULE_ID', 'ref.ID')
			))
				->configureJoinType('INNER')
			,
		];
	}
}