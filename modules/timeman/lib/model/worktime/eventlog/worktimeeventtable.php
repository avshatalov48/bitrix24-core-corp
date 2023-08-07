<?php
namespace Bitrix\Timeman\Model\Worktime\EventLog;

use \Bitrix\Main;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecordTable;

/**
 * Class WorktimeEventTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_WorktimeEvent_Query query()
 * @method static EO_WorktimeEvent_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_WorktimeEvent_Result getById($id)
 * @method static EO_WorktimeEvent_Result getList(array $parameters = array())
 * @method static EO_WorktimeEvent_Entity getEntity()
 * @method static \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent createObject($setDefaultValues = true)
 * @method static \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEventCollection createCollection()
 * @method static \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent wakeUpObject($row)
 * @method static \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEventCollection wakeUpCollection($rows)
 */
class WorktimeEventTable extends Main\ORM\Data\DataManager
{
	const EVENT_TYPE_START = 'START';
	const EVENT_TYPE_START_WITH_ANOTHER_TIME = 'START_WITH_ANOTHER_TIME';
	const EVENT_TYPE_PAUSE = 'PAUSE';
	const EVENT_TYPE_APPROVE = 'APPROVE';
	const EVENT_TYPE_CONTINUE = 'CONTINUE';
	const EVENT_TYPE_STOP = 'STOP';
	const EVENT_TYPE_STOP_WITH_ANOTHER_TIME = 'STOP_WITH_ANOTHER_TIME';
	const EVENT_TYPE_RELAUNCH = 'RELAUNCH';
	const EVENT_TYPE_EDIT_START = 'EDIT_START';
	const EVENT_TYPE_EDIT_STOP = 'EDIT_STOP';
	const EVENT_TYPE_EDIT_BREAK_LENGTH = 'EDIT_BREAK_LENGTH';

	const EVENT_TYPE_EDIT_WORKTIME = 'EDIT_WORKTIME';

	public static function getObjectClass()
	{
		return WorktimeEvent::class;
	}

	public static function getTableName()
	{
		return 'b_timeman_work_time_event_log';
	}

	public static function getCollectionClass()
	{
		return WorktimeEventCollection::class;
	}

	public static function getMap()
	{
		return [
			(new Fields\IntegerField('ID'))
				->configurePrimary(true)
				->configureAutocomplete(true)
			,
			(new Fields\IntegerField('USER_ID'))
			,
			(new Fields\EnumField('EVENT_TYPE'))
				->configureValues(static::getEventTypeRange())
			,
			(new Fields\StringField('EVENT_SOURCE'))
			,
			(new Fields\IntegerField('ACTUAL_TIMESTAMP'))
				->configureDefaultValue(function () {
					return TimeHelper::getInstance()->getUtcNowTimestamp();
				})
			,
			(new Fields\IntegerField('RECORDED_VALUE'))
			,
			(new Fields\IntegerField('RECORDED_OFFSET'))
			,
			(new Fields\IntegerField('WORKTIME_RECORD_ID'))
			,
			(new Fields\StringField('REASON'))
			,
			# relations
			(new Fields\Relations\Reference(
				'WORKTIME_RECORD',
				WorktimeRecordTable::class,
				Join::on('this.WORKTIME_RECORD_ID', 'ref.ID')
			))
				->configureJoinType('INNER')
			,
		];
	}

	public static function getEventTypeRange()
	{
		$reflection = new \ReflectionClass(__CLASS__);
		$constants = array_diff($reflection->getConstants(), $reflection->getParentClass()->getConstants());
		return array_filter($constants, function ($element) {
			return strncmp('EVENT_TYPE', $element, 10) === 0;
		}, ARRAY_FILTER_USE_KEY);
	}
}