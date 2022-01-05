<?php
namespace Bitrix\Timeman\Model\Schedule\ShiftPlan;

use \Bitrix\Main;
use \Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields;
use Bitrix\Timeman\Model\Schedule\Shift\ShiftTable;

Loc::loadMessages(__FILE__);

/**
 * Class ShiftPlanTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ShiftPlan_Query query()
 * @method static EO_ShiftPlan_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_ShiftPlan_Result getById($id)
 * @method static EO_ShiftPlan_Result getList(array $parameters = array())
 * @method static EO_ShiftPlan_Entity getEntity()
 * @method static \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan createObject($setDefaultValues = true)
 * @method static \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlanCollection createCollection()
 * @method static \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan wakeUpObject($row)
 * @method static \Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlanCollection wakeUpCollection($rows)
 */
class ShiftPlanTable extends Main\ORM\Data\DataManager
{
	const DATE_FORMAT = 'Y-m-d';
	const DELETED_YES = 1;
	const DELETED_NO = 0;

	public static function getDateRegExp()
	{
		return '#^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$#';
	}

	public static function getObjectClass()
	{
		return ShiftPlan::class;
	}

	public static function getCollectionClass()
	{
		return ShiftPlanCollection::class;
	}

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_timeman_work_shift_plan';
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
			(new Fields\IntegerField('SHIFT_ID'))
			,
			(new Fields\IntegerField('USER_ID'))
			,
			// pretend like we store date in utc
			(new Fields\DateField('DATE_ASSIGNED'))
			,
			(new Fields\BooleanField('DELETED'))
				->configureValues(static::DELETED_NO, static::DELETED_YES)
			,
			(new Fields\IntegerField('CREATED_AT'))
			,
			(new Fields\IntegerField('DELETED_AT'))
			,
			(new Fields\IntegerField('MISSED_SHIFT_AGENT_ID'))
				->configureDefaultValue(0)
			,

			# relations
			(new Fields\Relations\Reference('SHIFT', ShiftTable::class, Main\ORM\Query\Join::on('this.SHIFT_ID', 'ref.ID')))
			,
		];
	}
}