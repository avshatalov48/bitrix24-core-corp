<?php
namespace Bitrix\Tasks\Internals\Counter;

use Bitrix\Main\Entity\DataManager;

/**
 * Class CounterTable
 *
 * @package Bitrix\Tasks\Internals
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Counter_Query query()
 * @method static EO_Counter_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Counter_Result getById($id)
 * @method static EO_Counter_Result getList(array $parameters = [])
 * @method static EO_Counter_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\Counter\EO_Counter createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Counter\EO_Counter_Collection createCollection()
 * @method static \Bitrix\Tasks\Internals\Counter\EO_Counter wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Counter\EO_Counter_Collection wakeUpCollection($rows)
 */
class CounterTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_tasks_scorer';
	}

	public static function getClass(): string
	{
		return static::class;
	}

	public static function getMap(): array
	{
		return [
			'ID' => [
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			],
			'USER_ID' => [
				'data_type' => 'integer',
				'required' => true,
			],
			'TASK_ID' => [
				'data_type' => 'integer',
				'required' => true,
			],
			'GROUP_ID' => [
				'data_type' => 'integer',
				'required' => true,
			],
			'TYPE' => [
				'data_type' => 'string',
				'required' => true,
			],
			'VALUE' => [
				'data_type' => 'integer',
				'required' => true,
			],

			// references
			'USER' => [
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => ['=this.USER_ID' => 'ref.ID'],
			],
			'GROUP' => [
				'data_type' => 'Bitrix\Socialnetwork\Workgroup',
				'reference' => ['=this.GROUP_ID' => 'ref.ID'],
			],
			'TASK' => [
				'data_type' => 'Bitrix\Tasks\Internals\TaskTable',
				'reference' => ['=this.TASK_ID' => 'ref.ID'],
			],
		];
	}
}