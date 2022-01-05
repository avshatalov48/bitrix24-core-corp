<?php
namespace Bitrix\Timeman\Model\Security;

use \Bitrix\Main;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Fields;

/**
 * Class TaskAccessCodeTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_TaskAccessCode_Query query()
 * @method static EO_TaskAccessCode_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_TaskAccessCode_Result getById($id)
 * @method static EO_TaskAccessCode_Result getList(array $parameters = array())
 * @method static EO_TaskAccessCode_Entity getEntity()
 * @method static \Bitrix\Timeman\Model\Security\TaskAccessCode createObject($setDefaultValues = true)
 * @method static \Bitrix\Timeman\Model\Security\EO_TaskAccessCode_Collection createCollection()
 * @method static \Bitrix\Timeman\Model\Security\TaskAccessCode wakeUpObject($row)
 * @method static \Bitrix\Timeman\Model\Security\EO_TaskAccessCode_Collection wakeUpCollection($rows)
 */
class TaskAccessCodeTable extends Main\ORM\Data\DataManager
{
	public static function getObjectClass()
	{
		return TaskAccessCode::class;
	}

	public static function getTableName()
	{
		return 'b_timeman_task_access_code';
	}

	public static function getMap()
	{
		return [
			(new Fields\IntegerField('TASK_ID'))
				->configurePrimary(true)
			,
			(new Fields\StringField('ACCESS_CODE'))
				->configurePrimary(true)
			,
			# relations
			'TASK_OPERATION' => new ReferenceField(
				'TASK_OPERATION',
				\Bitrix\Main\TaskOperationTable::class,
				['=this.TASK_ID' => 'ref.TASK_ID']
			),
			'USER_ACCESS' => new ReferenceField(
				'USER_ACCESS',
				\Bitrix\Main\UserAccessTable::class,
				array('=this.ACCESS_CODE' => 'ref.ACCESS_CODE')
			),
		];
	}
}