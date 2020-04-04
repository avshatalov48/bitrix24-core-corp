<?php
namespace Bitrix\Timeman\Model\Security;

use \Bitrix\Main;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Fields;

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