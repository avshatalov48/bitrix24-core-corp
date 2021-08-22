<?php
namespace Bitrix\Timeman\Model\Schedule\Calendar;

use \Bitrix\Main;
use \Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Web\Json;

Loc::loadMessages(__FILE__);

/**
 * Class ShiftTable
 * @package Bitrix\Timeman\Model\Schedule\Shift
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_CalendarExclusion_Query query()
 * @method static EO_CalendarExclusion_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_CalendarExclusion_Result getById($id)
 * @method static EO_CalendarExclusion_Result getList(array $parameters = array())
 * @method static EO_CalendarExclusion_Entity getEntity()
 * @method static \Bitrix\Timeman\Model\Schedule\Calendar\CalendarExclusion createObject($setDefaultValues = true)
 * @method static \Bitrix\Timeman\Model\Schedule\Calendar\EO_CalendarExclusion_Collection createCollection()
 * @method static \Bitrix\Timeman\Model\Schedule\Calendar\CalendarExclusion wakeUpObject($row)
 * @method static \Bitrix\Timeman\Model\Schedule\Calendar\EO_CalendarExclusion_Collection wakeUpCollection($rows)
 */
class CalendarExclusionTable extends Main\ORM\Data\DataManager
{
	public static function getObjectClass()
	{
		return CalendarExclusion::class;
	}

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_timeman_work_calendar_exclusion';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			(new Fields\IntegerField('CALENDAR_ID'))
				->configurePrimary(true)
			,
			(new Fields\IntegerField('YEAR'))
				->configurePrimary(true)
			,
			(new Fields\ArrayField('DATES'))
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

			# relations
			(new Fields\Relations\Reference(
				'CALENDAR',
				CalendarTable::class,
				Join::on('this.CALENDAR_ID', 'ref.ID')
			))
				->configureJoinType('INNER')
			,
		];
	}
}