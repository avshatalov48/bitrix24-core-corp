<?php
namespace Bitrix\Timeman\Model\Schedule;

use \Bitrix\Main;
use \Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\EventResult;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Web\Json;
use Bitrix\Timeman\Helper\EntityCodesHelper;
use Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartmentTable;
use Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUserTable;
use Bitrix\Timeman\Model\Schedule\Calendar\CalendarTable;
use Bitrix\Timeman\Model\Schedule\Shift\ShiftTable;
use Bitrix\Timeman\Model\Schedule\Violation\ViolationRulesTable;

Loc::loadMessages(__FILE__);

/**
 * Class ScheduleTable
 * @package Bitrix\Timeman\Model\Schedule
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Schedule_Query query()
 * @method static EO_Schedule_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Schedule_Result getById($id)
 * @method static EO_Schedule_Result getList(array $parameters = array())
 * @method static EO_Schedule_Entity getEntity()
 * @method static \Bitrix\Timeman\Model\Schedule\Schedule createObject($setDefaultValues = true)
 * @method static \Bitrix\Timeman\Model\Schedule\ScheduleCollection createCollection()
 * @method static \Bitrix\Timeman\Model\Schedule\Schedule wakeUpObject($row)
 * @method static \Bitrix\Timeman\Model\Schedule\ScheduleCollection wakeUpCollection($rows)
 */
class ScheduleTable extends Main\ORM\Data\DataManager
{
	const SCHEDULE_TYPE_FIXED = 'FIXED';
	const SCHEDULE_TYPE_SHIFT = 'SHIFT';
	const SCHEDULE_TYPE_FLEXTIME = 'FLEXTIME';

	const REPORT_PERIOD_WEEK = 'WEEK';
	const REPORT_PERIOD_TWO_WEEKS = 'TWO_WEEKS';
	const REPORT_PERIOD_MONTH = 'MONTH';
	const REPORT_PERIOD_QUARTER = 'QUARTER';

	const REPORT_PERIOD_OPTIONS_START_WEEK_DAY = 'START_WEEK_DAY';
	const REPORT_PERIOD_OPTIONS_START_WEEK_DAY_MONDAY = 0;
	const REPORT_PERIOD_OPTIONS_START_WEEK_DAY_TUESDAY = 1;
	const REPORT_PERIOD_OPTIONS_START_WEEK_DAY_WEDNESDAY = 2;
	const REPORT_PERIOD_OPTIONS_START_WEEK_DAY_THURSDAY = 3;
	const REPORT_PERIOD_OPTIONS_START_WEEK_DAY_FRIDAY = 4;
	const REPORT_PERIOD_OPTIONS_START_WEEK_DAY_SATURDAY = 5;
	const REPORT_PERIOD_OPTIONS_START_WEEK_DAY_SUNDAY = 6;

	const CONTROLLED_ACTION_START = 1;
	const CONTROLLED_ACTION_END = 2;
	const CONTROLLED_ACTION_START_AND_END = 3;

	const ALLOWED_DEVICES_MOBILE = 'mobile';
	const ALLOWED_DEVICES_B24TIME = 'b24time';
	const ALLOWED_DEVICES_BROWSER = 'browser';

	const DELETED_YES = 1;
	const DELETED_NO = 0;

	const WORKTIME_RESTRICTION_ALLOWED_TO_EDIT_RECORD = 'ALLOWED_TO_EDIT_RECORD';
	const WORKTIME_RESTRICTION_ALLOWED_TO_REOPEN_RECORD = 'ALLOWED_TO_REOPEN_RECORD';

	const WORKTIME_RESTRICTION_MAX_SHIFT_START_OFFSET = 'MAX_SHIFT_START_OFFSET';

	public static function getWorktimeRestrictionsKeys()
	{
		$reflection = new \ReflectionClass(__CLASS__);
		$constants = array_diff($reflection->getConstants(), $reflection->getParentClass()->getConstants());
		return array_filter($constants, function ($element) {
			return strncmp('WORKTIME_RESTRICTION_', $element, mb_strlen('WORKTIME_RESTRICTION_')) === 0;
		}, ARRAY_FILTER_USE_KEY);
	}

	public static function getObjectClass()
	{
		return Schedule::class;
	}

	public static function getCollectionClass()
	{
		return ScheduleCollection::class;
	}

	public static function onBeforeUpdate(Event $event)
	{
		$result = new EventResult;
		$data = $event->getParameter('fields');
		global $USER;
		if ($USER && is_object($USER))
		{
			$data['UPDATED_BY'] = $USER->GetID();
			$result->modifyFields($data);
		}

		return $result;
	}

	public static function getControlledActionTypes()
	{
		return [
			static::CONTROLLED_ACTION_START,
			static::CONTROLLED_ACTION_START_AND_END,
		];
	}

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_timeman_work_schedule';
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
			,
			(new Fields\EnumField('SCHEDULE_TYPE'))
				->configureValues([
					static::SCHEDULE_TYPE_FIXED,
					static::SCHEDULE_TYPE_FLEXTIME,
					static::SCHEDULE_TYPE_SHIFT,
				])
				->configureDefaultValue(static::SCHEDULE_TYPE_FIXED)
			,
			(new Fields\EnumField('REPORT_PERIOD'))
				->configureValues([
					static::REPORT_PERIOD_WEEK,
					static::REPORT_PERIOD_TWO_WEEKS,
					static::REPORT_PERIOD_MONTH,
					static::REPORT_PERIOD_QUARTER,
				])
				->configureDefaultValue(static::REPORT_PERIOD_MONTH)
			,
			(new Fields\ArrayField('REPORT_PERIOD_OPTIONS'))
				->configureSerializeCallback(function ($value) {
					try
					{
						return Json::encode($value);
					}
					catch (\Exception $exc)
					{
						return Json::encode([]);
					}
				})
				->configureUnserializeCallback(function ($value) {
					try
					{
						return Json::decode($value);
					}
					catch (\Exception $exc)
					{
						return [];
					}
				})
			,
			(new Fields\IntegerField('CALENDAR_ID'))
			,
			(new Fields\ArrayField('ALLOWED_DEVICES'))
				->configureSerializeCallback(function ($value) {
					try
					{
						return Json::encode($value);
					}
					catch (\Exception $exc)
					{
						return Json::encode([]);
					}
				})
				->configureUnserializeCallback(function ($value) {
					try
					{
						return Json::decode($value);
					}
					catch (\Exception $exc)
					{
						return Json::decode('[]');
					}
				})
			,
			(new Fields\EnumField('DELETED'))
				->configureValues([
					static::DELETED_YES,
					static::DELETED_NO,
				])
				->configureDefaultValue(static::DELETED_NO)
			,
			(new Fields\BooleanField('IS_FOR_ALL_USERS'))
				->configureDefaultValue(0)
				->configureValues(0, 1)
			,
			(new Fields\ArrayField('WORKTIME_RESTRICTIONS'))
				->configureDefaultValue([])
				->configureSerializeCallback(function ($value) {
					try
					{
						return Json::encode($value);
					}
					catch (\Exception $exc)
					{
						return Json::encode([]);
					}
				})
				->configureUnserializeCallback(function ($value) {
					try
					{
						return Json::decode($value);
					}
					catch (\Exception $exc)
					{
						return [];
					}
				})
			,
			(new Fields\IntegerField('CONTROLLED_ACTIONS'))
			,
			(new Fields\IntegerField('UPDATED_BY'))
			,
			(new Fields\IntegerField('DELETED_BY'))
			,
			(new Fields\StringField('DELETED_AT'))
			,
			(new Fields\IntegerField('CREATED_BY'))
				->configureDefaultValue(function () {
					global $USER;
					if ($USER && is_object($USER))
					{
						return $USER->GetID();
					}
					return 0;
				})
			,
			(new Fields\DatetimeField('CREATED_AT'))
				->configureDefaultValue(function () {
					return new Main\Type\DateTime();
				})
			,

			# relations
			(new OneToMany('SHIFTS', ShiftTable::class, 'SCHEDULE')) // active, not deleted
				->configureJoinType('left')
			,
			(new OneToMany('ALL_SHIFTS', ShiftTable::class, 'SCHEDULE_WITH_ALL_SHIFTS')) // deleted too
				->configureJoinType('left')
			,
			(new Reference(
				'SCHEDULE_VIOLATION_RULES',
				ViolationRulesTable::class,
				Join::on('this.ID', 'ref.SCHEDULE_ID')->where('ref.ENTITY_CODE', EntityCodesHelper::getAllUsersCode())
			))
				->configureJoinType('LEFT')
			,
			(new Reference(
				'CALENDAR',
				CalendarTable::class,
				Join::on('this.CALENDAR_ID', 'ref.ID')
			))
				->configureJoinType('LEFT')
			,
			(new OneToMany('USER_ASSIGNMENTS', ScheduleUserTable::class, 'SCHEDULE'))
				->configureJoinType('LEFT')
			,
			(new OneToMany('DEPARTMENT_ASSIGNMENTS', ScheduleDepartmentTable::class, 'SCHEDULE'))
				->configureJoinType('LEFT')
			,
		];
	}
}