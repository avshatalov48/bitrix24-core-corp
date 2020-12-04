<?php


namespace Bitrix\Tasks\Internals\Counter;

use Bitrix\Main\Entity\DataManager;

class CounterQueueTable extends DataManager
{

	public static function getTableName(): string
	{
		return 'b_tasks_scorer_queue';
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
			'TYPE' => [
				'data_type' => 'string',
				'required' => true,
			],
			'TASK_ID' => [
				'data_type' => 'integer',
				'required' => true,
			],
			'DATETIME' => [
				'data_type' => 'datetime'
			]
		];
	}

}