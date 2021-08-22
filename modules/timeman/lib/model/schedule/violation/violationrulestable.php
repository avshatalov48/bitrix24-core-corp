<?php
namespace Bitrix\Timeman\Model\Schedule\Violation;

use \Bitrix\Main;
use \Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\Web\Json;

Loc::loadMessages(__FILE__);

/**
 * Class ViolationRulesTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ViolationRules_Query query()
 * @method static EO_ViolationRules_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_ViolationRules_Result getById($id)
 * @method static EO_ViolationRules_Result getList(array $parameters = array())
 * @method static EO_ViolationRules_Entity getEntity()
 * @method static \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules createObject($setDefaultValues = true)
 * @method static \Bitrix\Timeman\Model\Schedule\Violation\ViolationRulesCollection createCollection()
 * @method static \Bitrix\Timeman\Model\Schedule\Violation\ViolationRules wakeUpObject($row)
 * @method static \Bitrix\Timeman\Model\Schedule\Violation\ViolationRulesCollection wakeUpCollection($rows)
 */
class ViolationRulesTable extends Main\ORM\Data\DataManager
{
	const USERS_TO_NOTIFY_USER_MANAGER = 'USER_MANAGER';

	const USERS_TO_NOTIFY_FIXED_START_END = 'FIXED_START_END';
	const USERS_TO_NOTIFY_FIXED_RECORD_TIME_PER_DAY = 'FIXED_PER_RECORD';
	const USERS_TO_NOTIFY_FIXED_EDIT_WORKTIME = 'FIXED_EDIT_WORKTIME';
	const USERS_TO_NOTIFY_FIXED_TIME_FOR_PERIOD = 'FIXED_PERIODIC';
	const USERS_TO_NOTIFY_SHIFT_DELAY = 'SHIFT_DELAY';
	const USERS_TO_NOTIFY_SHIFT_MISSED_START = 'SHIFT_MISSED_START';

	const MISSED_SHIFT_IS_TRACKED = 1;
	const MISSED_SHIFT_IS_NOT_TRACKED = 0;

	public static function getObjectClass()
	{
		return ViolationRules::class;
	}

	public static function getCollectionClass()
	{
		return ViolationRulesCollection::class;
	}

	public static function getTableName()
	{
		return 'b_timeman_work_schedule_violation_rules';
	}

	public static function getMap()
	{
		return [
			(new Fields\IntegerField('ID'))
				->configurePrimary(true)
				->configureAutocomplete(true)
			,
			(new Fields\IntegerField('SCHEDULE_ID'))
			,
			(new Fields\StringField('ENTITY_CODE'))
			,
			(new Fields\IntegerField('MAX_EXACT_START'))
				->configureDefaultValue(-1)
			,
			(new Fields\IntegerField('MIN_EXACT_END'))
				->configureDefaultValue(-1)
			,
			(new Fields\IntegerField('MAX_OFFSET_START'))
				->configureDefaultValue(-1)
			,
			(new Fields\IntegerField('MIN_OFFSET_END'))
				->configureDefaultValue(-1)
			,
			(new Fields\IntegerField('RELATIVE_START_FROM'))
				->configureDefaultValue(-1)
			,
			(new Fields\IntegerField('RELATIVE_START_TO'))
				->configureDefaultValue(-1)
			,
			(new Fields\IntegerField('RELATIVE_END_FROM'))
				->configureDefaultValue(-1)
			,
			(new Fields\IntegerField('RELATIVE_END_TO'))
				->configureDefaultValue(-1)
			,
			(new Fields\IntegerField('MIN_DAY_DURATION'))
				->configureDefaultValue(-1)
			,
			(new Fields\IntegerField('MAX_ALLOWED_TO_EDIT_WORK_TIME'))
				->configureDefaultValue(-1)
			,
			(new Fields\IntegerField('MAX_WORK_TIME_LACK_FOR_PERIOD'))
				->configureDefaultValue(-1)
			,
			(new Fields\IntegerField('PERIOD_TIME_LACK_AGENT_ID'))
				->configureDefaultValue(0)
			,
			(new Fields\IntegerField('MAX_SHIFT_START_DELAY'))
				->configureDefaultValue(-1)
			,
			(new Fields\IntegerField('MISSED_SHIFT_START'))
				->configureDefaultValue(-1)
			,
			(new Fields\ArrayField('USERS_TO_NOTIFY'))
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
		];
	}
}