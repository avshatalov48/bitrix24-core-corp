<?php
namespace Bitrix\Timeman\Model\Worktime\Record;

use \Bitrix\Main;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\EventResult;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Timeman\Helper\UserHelper;
use Bitrix\Timeman\Model\User\UserTable;
use Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEventTable;
use Bitrix\Timeman\Service\DependencyManager;

/**
 * Class WorktimeRecordTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_WorktimeRecord_Query query()
 * @method static EO_WorktimeRecord_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_WorktimeRecord_Result getById($id)
 * @method static EO_WorktimeRecord_Result getList(array $parameters = array())
 * @method static EO_WorktimeRecord_Entity getEntity()
 * @method static \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord createObject($setDefaultValues = true)
 * @method static \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecordCollection createCollection()
 * @method static \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord wakeUpObject($row)
 * @method static \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecordCollection wakeUpCollection($rows)
 */
class WorktimeRecordTable extends Main\ORM\Data\DataManager
{
	private static $writeCompatibleFields = true;

	const STATUS_OPENED = 'OPENED';
	const STATUS_PAUSED = 'PAUSED';
	const STATUS_CLOSED = 'CLOSED';

	const APPROVED_YES = 1;
	const APPROVED_NO = 0;

	public static function getObjectClass()
	{
		return WorktimeRecord::class;
	}

	public static function getTableName()
	{
		return 'b_timeman_entries';
	}

	public static function getCollectionClass()
	{
		return WorktimeRecordCollection::class;
	}

	public static function getMap()
	{
		return [
			(new Fields\IntegerField('ID'))
				->configurePrimary(true)
				->configureAutocomplete(true)
			,
			(new Fields\IntegerField('USER_ID'))
				->configureDefaultValue(0)
			,
			(new Fields\IntegerField('RECORDED_START_TIMESTAMP'))
				->configureDefaultValue(0)
			,
			(new Fields\IntegerField('START_OFFSET'))
				->configureDefaultValue(0)
			,
			(new Fields\IntegerField('ACTUAL_START_TIMESTAMP'))
				->configureDefaultValue(0)
			,
			(new Fields\IntegerField('RECORDED_STOP_TIMESTAMP'))
				->configureDefaultValue(0)
			,
			(new Fields\IntegerField('STOP_OFFSET'))
				->configureDefaultValue(0)
			,
			(new Fields\IntegerField('ACTUAL_STOP_TIMESTAMP'))
				->configureDefaultValue(0)
			,
			(new Fields\StringField('CURRENT_STATUS'))
				->configureDefaultValue('')
			,
			(new Fields\IntegerField('DURATION'))
				->configureDefaultValue(0)
			,
			(new Fields\IntegerField('RECORDED_DURATION'))
				->configureDefaultValue(0)
			,
			(new Fields\IntegerField('TIME_LEAKS'))
				->configureDefaultValue(0)
			,
			(new Fields\IntegerField('ACTUAL_BREAK_LENGTH'))
				->configureDefaultValue(0)
			,
			(new Fields\IntegerField('SCHEDULE_ID'))
				->configureDefaultValue(0)
			,
			(new Fields\IntegerField('SHIFT_ID'))
				->configureDefaultValue(0)
			,
			(new Fields\IntegerField('AUTO_CLOSING_AGENT_ID'))
				->configureDefaultValue(0)
			,
			(new Fields\BooleanField('APPROVED'))
				->configureValues(static::APPROVED_NO, static::APPROVED_YES)
				->configureDefaultValue(true)
			,
			(new Fields\DatetimeField('TIMESTAMP_X'))
				->configureDefaultValue(function () {
					return new \Bitrix\Main\Type\DateTime();
				})
			,
			(new Fields\IntegerField('MODIFIED_BY'))
				->configureDefaultValue(0)
			,
			(new Fields\IntegerField('APPROVED_BY'))
				->configureDefaultValue(0)
			,
			(new Fields\BooleanField('ACTIVE'))
				->configureValues('N', 'Y')
				->configureDefaultValue('Y')
			,
			(new Fields\BooleanField('PAUSED'))
				->configureValues('N', 'Y')
				->configureDefaultValue('N')
			,
			(new Fields\DatetimeField('DATE_START'))
			,
			(new Fields\DatetimeField('DATE_FINISH'))
				->configureDefaultValue(null)
			,
			(new Fields\IntegerField('TIME_START'))
				->configureDefaultValue(0)
			,
			(new Fields\IntegerField('TIME_FINISH'))
				->configureDefaultValue(null)
			,
			(new Fields\ArrayField('TASKS'))
				->configureSerializeCallback(function ($value) {
					if ($value)
					{
						return serialize($value);
					}
					return null;
				})
				->configureUnserializeCallback(function ($value) {
					$res = unserialize($value, ['allowed_classes' => false]);
					return $res === false ? [] : $res;
				})
			,
			(new Fields\StringField('IP_OPEN'))
				->addValidator(new Fields\Validators\LengthValidator(null, 50))
				->configureDefaultValue('')
			,
			(new Fields\StringField('IP_CLOSE'))
				->addValidator(new Fields\Validators\LengthValidator(null, 50))
				->configureDefaultValue('')
			,
			(new Fields\IntegerField('FORUM_TOPIC_ID'))
			,
			(new Fields\FloatField('LAT_OPEN'))
			,
			(new Fields\FloatField('LON_OPEN'))
			,
			(new Fields\FloatField('LAT_CLOSE'))
			,
			(new Fields\FloatField('LON_CLOSE'))
			,

			# relations

			(new Fields\Relations\Reference(
				'USER',
				UserTable::class,
				Join::on('this.USER_ID', 'ref.ID')
			))
				->configureJoinType('INNER')
			,
			(new OneToMany('WORKTIME_EVENTS', WorktimeEventTable::class, 'WORKTIME_RECORD'))
				->configureJoinType('LEFT')
			,
		];
	}

	public static function convertFieldsCompatible($fields)
	{
		if (array_key_exists('PAUSED', $fields))
		{
			$fields['PAUSED'] = $fields['PAUSED'] === true ? 'Y' : 'N';
		}
		if (array_key_exists('ACTIVE', $fields))
		{
			$fields['ACTIVE'] = $fields['ACTIVE'] === true ? 'Y' : 'N';
		}
		return $fields;
	}

	public static function onBeforeAdd(Event $event)
	{
		$result = new EventResult;
		$data = $event->getParameter('fields');
		if (static::$writeCompatibleFields)
		{
			$data = static::fillFieldsForCompatibility($data);
			$result->modifyFields($data);
		}
		DependencyManager::getInstance()->getWorktimeEventsManager()
			->sendModuleEventsOnBeforeAddRecord($data, $result);

		return $result;
	}

	public static function onAfterAdd(Event $event)
	{
		$result = new EventResult;
		DependencyManager::getInstance()->getWorktimeEventsManager()
			->sendModuleEventsOnAfterRecordAdd($event->getParameter('fields'));

		return $result;
	}

	public static function onBeforeUpdate(Event $event)
	{
		$result = new EventResult;
		$data = $event->getParameter('fields');

		if (static::$writeCompatibleFields)
		{
			$data = static::fillFieldsForCompatibility($data);
			$result->modifyFields($data);
		}
		DependencyManager::getInstance()->getWorktimeEventsManager()
			->sendModuleEventsOnBeforeRecordUpdate($data, $result);

		return $result;
	}

	public static function onAfterUpdate(Event $event)
	{
		$eventManager = DependencyManager::getInstance()->getWorktimeEventsManager();

		$result = new EventResult;
		$fields = $event->getParameter('fields');
		$id = $eventManager->extractIdFromEvent($event);

		if (!isset($fields['USER_ID']))
		{
			$record = static::query()
				->addSelect('USER_ID')
				->where('ID', $id)
				->exec()
				->fetch();
			if ($record)
			{
				$fields['USER_ID'] = $record['USER_ID'];
			}
		}

		$eventManager->sendModuleEventsOnAfterRecordUpdate(
			$id,
			$fields
		);

		return $result;
	}

	private static function issetKey($key, $data)
	{
		return array_key_exists($key, $data) && (int)$data[$key] !== 0 && $data[$key] !== null;
	}

	public static function fillFieldsForCompatibility($data)
	{
		if (!array_key_exists('MODIFIED_BY', $data))
		{
			$data['MODIFIED_BY'] = UserHelper::getCurrentUserId();
		}
		return $data;
	}

	public static function getStatusRange()
	{
		return [
			static::STATUS_PAUSED,
			static::STATUS_OPENED,
			static::STATUS_CLOSED,
		];
	}
}