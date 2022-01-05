<?php
namespace Bitrix\Timeman\Model\Schedule\Assignment\Department;

use \Bitrix\Main;
use \Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Timeman\Model\Schedule\ScheduleTable;

Loc::loadMessages(__FILE__);

/**
 * Class ScheduleDepartmentTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ScheduleDepartment_Query query()
 * @method static EO_ScheduleDepartment_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_ScheduleDepartment_Result getById($id)
 * @method static EO_ScheduleDepartment_Result getList(array $parameters = array())
 * @method static EO_ScheduleDepartment_Entity getEntity()
 * @method static \Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartment createObject($setDefaultValues = true)
 * @method static \Bitrix\Timeman\Model\Schedule\Assignment\Department\EO_ScheduleDepartment_Collection createCollection()
 * @method static \Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartment wakeUpObject($row)
 * @method static \Bitrix\Timeman\Model\Schedule\Assignment\Department\EO_ScheduleDepartment_Collection wakeUpCollection($rows)
 */
class ScheduleDepartmentTable extends Main\ORM\Data\DataManager
{
	const INCLUDED = 0;
	const EXCLUDED = 1;

	public static function getObjectClass()
	{
		return ScheduleDepartment::class;
	}

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_timeman_work_schedule_department';
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
				->configureJoinType('inner')
			,
			(new Fields\IntegerField('DEPARTMENT_ID'))
				->configurePrimary(true)
			,
			(new Reference('DEPARTMENT', \Bitrix\Iblock\SectionTable::class,
				Join::on('this.DEPARTMENT_ID', 'ref.ID')))
				->configureJoinType('inner')
			,
			(new Fields\IntegerField('STATUS'))
			,
		];
	}
}