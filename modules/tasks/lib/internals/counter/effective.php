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
 * @method static EO_Effective_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Effective_Result getById($id)
 * @method static EO_Effective_Result getList(array $parameters = [])
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
		return [
			'ID' => [
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			],
			'DATETIME' => [
				'data_type' => 'datetime',
				'required' => true,
			],
			'DATETIME_REPAIR' => [
				'data_type' => 'datetime',
				'required' => false,
			],
			'USER_ID' => [
				'data_type' => 'integer',
				'required' => true,
			],
			'USER_TYPE' => [
				'data_type' => 'string',
				'required' => false,
			],
			'GROUP_ID' => [
				'data_type' => 'integer',
				'required' => false,
			],
			'EFFECTIVE' => [
				'data_type' => 'integer',
			],
			'TASK_ID' => [
				'data_type' => 'integer',
				'required' => false,
			],
			'TASK_TITLE' => [
				'data_type' => 'string',
				'required' => false,
				'default' => 'N',
				'save_data_modification' => ['\Bitrix\Main\Text\Emoji', 'getSaveModificator'],
				'fetch_data_modification' => ['\Bitrix\Main\Text\Emoji', 'getFetchModificator'],
			],
			'TASK_DEADLINE' => [
				'data_type' => 'datetime',
				'required' => false,
				'default' => 'N',
			],
			'IS_VIOLATION' => [
				'data_type' => 'string',
				'required' => false,
				'default'=> 'N',
			],
			// references
			'USER' => [
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => ['=this.USER_ID' => 'ref.ID'],
			],
			'GROUP' => [
				'data_type' => 'Bitrix\Socialnetwork\WorkgroupTable',
				'reference' => ['=this.GROUP_ID' => 'ref.ID'],
			],
			'TASK' => [
				'data_type' => 'Bitrix\Tasks\TaskTable',
				'reference' => ['=this.TASK_ID' => 'ref.ID'],
			],
			'RECYCLE' => [
				'data_type' => 'Bitrix\Recyclebin\Internals\Models\RecyclebinTable',
				'reference' => [
					'=ref.ENTITY_TYPE' => ['?', Manager::TASKS_RECYCLEBIN_ENTITY],
					'=this.TASK_ID' => 'ref.ENTITY_ID',
				],
			],
		];
	}
}