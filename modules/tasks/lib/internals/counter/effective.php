<?php
namespace Bitrix\Tasks\Internals\Counter;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Tasks\Integration\Recyclebin\Manager;

/**
 * Class EffectiveTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Effective_Query query()
 * @method static EO_Effective_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Effective_Result getById($id)
 * @method static EO_Effective_Result getList(array $parameters = array())
 * @method static EO_Effective_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\Counter\EO_Effective createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Counter\EO_Effective_Collection createCollection()
 * @method static \Bitrix\Tasks\Internals\Counter\EO_Effective wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Counter\EO_Effective_Collection wakeUpCollection($rows)
 */
class EffectiveTable extends DataManager
{
	public static function getTableName()
	{
		return 'b_tasks_effective';
	}

	public static function getClass()
	{
		return get_called_class();
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true
			),

			'DATETIME' => array(
				'data_type' => 'datetime',
				'required' => true
			),
			'DATETIME_REPAIR' => array(
				'data_type' => 'datetime',
				'required' => false
			),
			'USER_TYPE' => array(
				'data_type' => 'string',
				'required' => false
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'required' => true
			),
			'GROUP_ID' => array(
				'data_type' => 'integer',
				'required' => false
			),

			'EFFECTIVE' => array(
				'data_type' => 'integer'
			),

			'TASK_ID' => array(
				'data_type' => 'integer',
				'required' => false
			),

			'TASK_TITLE' => array(
				'data_type' => 'string',
				'required' => false,
				'default' => 'N'
			),
			'TASK_DEADLINE' => array(
				'data_type' => 'datetime',
				'required' => false,
				'default' => 'N'
			),


			'IS_VIOLATION' => array(
				'data_type' => 'string',
				'required' => false,
				'default'=> 'N'
			),

			// references
			'USER' => array(
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => array('=this.USER_ID' => 'ref.ID')
			),

			'GROUP' => array(
				'data_type' => 'Bitrix\Socialnetwork\WorkgroupTable',
				'reference' => array('=this.GROUP_ID' => 'ref.ID')
			),

			'TASK' => array(
				'data_type' => 'Bitrix\Tasks\TaskTable',
				'reference' => array('=this.TASK_ID' => 'ref.ID')
			),

			'RECYCLE' => array(
				'data_type' => 'Bitrix\Recyclebin\Internals\Models\RecyclebinTable',
				'reference' => [
					'=ref.ENTITY_TYPE' => ['?', Manager::TASKS_RECYCLEBIN_ENTITY],
					'=this.TASK_ID' => 'ref.ENTITY_ID'
				]
			)
		);
	}
}