<?php
namespace Bitrix\Timeman\Model\Schedule\ShiftPlan;

use \Bitrix\Main;
use \Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields;
use Bitrix\Timeman\Model\Schedule\Shift\ShiftTable;

Loc::loadMessages(__FILE__);

class ShiftPlanTable extends Main\ORM\Data\DataManager
{
	const DATE_FORMAT = 'Y-m-d';

	public static function getObjectClass()
	{
		return ShiftPlan::class;
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
			(new Fields\IntegerField('SHIFT_ID'))
				->configurePrimary(true)
			,
			(new Fields\IntegerField('USER_ID'))
				->configurePrimary(true)
			,
			(new Fields\DateField('DATE_ASSIGNED'))
				->configurePrimary(true)
			,
			# relations
			(new Fields\Relations\Reference('SHIFT', ShiftTable::class, Main\ORM\Query\Join::on('this.SHIFT_ID', 'ref.ID')))
			,
		];
	}
}