<?php
namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main;

/**
 * Class UserOptionTable
 *
 * @package Bitrix\Tasks\Internals\Task
 */
class UserOptionTable extends Main\Entity\DataManager
{
	/**
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_tasks_user_option';
	}

	/**
	 * @return array
	 */
	public static function getMap(): array
	{
		return [
			'ID' => [
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			],
			'TASK_ID' => [
				'data_type' => 'integer',
				'required' => true,
			],
			'USER_ID' => [
				'data_type' => 'integer',
				'required' => true,
			],
			'OPTION_CODE' => [
				'data_type' => 'integer',
				'required' => true,
			],
		];
	}

	/**
	 * @param int $taskId
	 * @throws Main\Db\SqlQueryException
	 */
	public static function deleteByTaskId(int $taskId): void
	{
		$tableName = static::getTableName();

		$connection = Main\Application::getConnection();
		$connection->query("
			DELETE FROM {$tableName}
			WHERE TASK_ID = {$taskId};
		");
	}

	/**
	 * @param int $userId
	 * @throws Main\Db\SqlQueryException
	 */
	public static function deleteByUserId(int $userId): void
	{
		$tableName = static::getTableName();

		$connection = Main\Application::getConnection();
		$connection->query("
			DELETE FROM {$tableName}
			WHERE USER_ID = {$userId};
		");
	}

	/**
	 * @param int $taskId
	 * @param int $userId
	 * @throws Main\Db\SqlQueryException
	 */
	public static function deleteByTaskIdAndUserId(int $taskId, int $userId): void
	{
		$tableName = static::getTableName();

		$connection = Main\Application::getConnection();
		$connection->query("
			DELETE FROM {$tableName}
			WHERE TASK_ID = {$taskId} AND USER_ID = {$userId}
		");
	}
}