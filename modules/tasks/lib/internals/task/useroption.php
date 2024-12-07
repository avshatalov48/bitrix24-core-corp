<?php
namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main;
use Bitrix\Main\Type\Collection;

/**
 * Class UserOptionTable
 *
 * @package Bitrix\Tasks\Internals\Task
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_UserOption_Query query()
 * @method static EO_UserOption_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_UserOption_Result getById($id)
 * @method static EO_UserOption_Result getList(array $parameters = [])
 * @method static EO_UserOption_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\Task\EO_UserOption createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Task\EO_UserOption_Collection createCollection()
 * @method static \Bitrix\Tasks\Internals\Task\EO_UserOption wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Task\EO_UserOption_Collection wakeUpCollection($rows)
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

	public static function deleteByOptions(int $taskId, int $userId, array $options): void
	{
		if ($taskId <= 0 || $userId <= 0)
		{
			return;
		}

		Collection::normalizeArrayValuesByInt($options, false);
		if (empty($options))
		{
			return;
		}

		$options = '(' . implode(',', $options) . ')';

		$tableName = static::getTableName();
		$connection = Main\Application::getConnection();
		$helper = $connection->getSqlHelper();

		$connection->query("
			delete 
			from {$helper->quote($tableName)} 
			where {$helper->quote('TASK_ID')} = {$taskId}
			and {$helper->quote('USER_ID')} = {$userId}
			and {$helper->quote('OPTION_CODE')} in ({$options})
		");
	}
}