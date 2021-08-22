<?php
namespace Bitrix\Timeman\Model\Schedule\Calendar;

use \Bitrix\Main;
use \Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Query\Join;

Loc::loadMessages(__FILE__);

/**
 * Class ShiftTable
 * @package Bitrix\Timeman\Model\Schedule\Shift
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Calendar_Query query()
 * @method static EO_Calendar_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Calendar_Result getById($id)
 * @method static EO_Calendar_Result getList(array $parameters = array())
 * @method static EO_Calendar_Entity getEntity()
 * @method static \Bitrix\Timeman\Model\Schedule\Calendar\Calendar createObject($setDefaultValues = true)
 * @method static \Bitrix\Timeman\Model\Schedule\Calendar\EO_Calendar_Collection createCollection()
 * @method static \Bitrix\Timeman\Model\Schedule\Calendar\Calendar wakeUpObject($row)
 * @method static \Bitrix\Timeman\Model\Schedule\Calendar\EO_Calendar_Collection wakeUpCollection($rows)
 */
class CalendarTable extends Main\ORM\Data\DataManager
{
	const SYSTEM_CODE_NONE = '';
	const SYSTEM_CODE_RUSSIA = 'ru';
	const SYSTEM_CODE_UKRAINE = 'ua';
	const SYSTEM_CODE_BELARUS = 'by';
	const SYSTEM_CODE_KAZAKHSTAN = 'kz';
	const SYSTEM_CODE_USA = 'usa';
	const SYSTEM_CODE_GERMANY = 'de';
	const SYSTEM_CODE_BRAZIL = 'br';
	const SYSTEM_CODE_INDIA = 'in';
	const SYSTEM_CODE_VIETNAM = 'vn';
	const SYSTEM_CODE_MEXICO = 'mx';
	const SYSTEM_CODE_FRANCE = 'fr';
	const SYSTEM_CODE_INDONESIA = 'id';
	const SYSTEM_CODE_POLAND = 'pl';
	const SYSTEM_CODE_JAPAN = 'jp';

	public static function getObjectClass()
	{
		return Calendar::class;
	}

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_timeman_work_calendar';
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
			(new Fields\IntegerField('PARENT_CALENDAR_ID'))
				->configureDefaultValue(function () {
					return 0;
				})
			,
			(new Fields\EnumField('SYSTEM_CODE'))
				->configureValues(static::getAllSystemCodes())
				->configureDefaultValue(static::SYSTEM_CODE_NONE)
			,

			# relations
			(new Fields\Relations\OneToMany('EXCLUSIONS', CalendarExclusionTable::class, 'CALENDAR'))
				->configureJoinType('LEFT')
			,
			(new Fields\Relations\Reference('PARENT_CALENDAR', CalendarTable::class,
				Join::on('this.PARENT_CALENDAR_ID', 'ref.ID')))
				->configureJoinType('LEFT')
			,
		];
	}

	public static function getSystemCalendarCodes()
	{
		$defaultCode = static::SYSTEM_CODE_NONE;
		return array_filter(static::getAllSystemCodes(), function ($code) use ($defaultCode) {
			return $defaultCode !== $code;
		});
	}

	public static function getAllSystemCodes()
	{
		$reflection = new \ReflectionClass(__CLASS__);
		$constants = array_diff($reflection->getConstants(), $reflection->getParentClass()->getConstants());
		return array_filter($constants, function ($element) {
			return strncmp('SYSTEM_CODE_', $element, mb_strlen('SYSTEM_CODE_')) === 0;
		}, ARRAY_FILTER_USE_KEY);
	}
}