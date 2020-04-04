<?php
namespace Bitrix\Timeman\Model\Schedule\Assignment\User;

use \Bitrix\Main;
use \Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Timeman\Model\Schedule\ScheduleTable;

Loc::loadMessages(__FILE__);

/**
 * Class ScheduleTable
 * @package Bitrix\Timeman\Model\Schedule
 */
class ScheduleUserTable extends Main\ORM\Data\DataManager
{
	const INCLUDED = 0;
	const EXCLUDED = 1;

	public static function getObjectClass()
	{
		return ScheduleUser::class;
	}

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_timeman_work_schedule_user';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			(new Fields\IntegerField('SCHEDULE_ID'))
				->configurePrimary(true)
			,
			(new Reference('SCHEDULE', ScheduleTable::class,
				Join::on('this.SCHEDULE_ID', 'ref.ID')))
				->configureJoinType('inner'),
			(new Fields\IntegerField('USER_ID'))
				->configurePrimary(true)
			,
			(new Reference('USER', \Bitrix\Main\UserTable::class,
				Join::on('this.USER_ID', 'ref.ID')))
				->configureJoinType('inner')
			,
			(new Fields\IntegerField('STATUS')) // rename it back to excluded/included
			,
		];
	}
}