<?php
namespace Bitrix\Tasks\Internals\Counter;

use Bitrix\Main\Entity\DataManager;

/**
 * Class CounterTable
 *
 * @package Bitrix\Tasks\Internals
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