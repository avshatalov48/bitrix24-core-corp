<?php
namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main;

/**
 * Class ProjectUserOptionTable
 *
 * @package Bitrix\Tasks\Internals\Task
 */
class ProjectUserOptionTable extends Main\Entity\DataManager
{
	/**
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_tasks_project_user_option';
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
			'PROJECT_ID' => [
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
	 * @param int $projectId
	 * @throws Main\Db\SqlQueryException
	 */
	public static function deleteByProjectId(int $projectId): void
	{
		$tableName = static::getTableName();

		$connection = Main\Application::getConnection();
		$connection->query("
			DELETE FROM {$tableName}
			WHERE PROJECT_ID = {$projectId};
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
	 * @param int $projectId
	 * @param int $userId
	 * @throws Main\Db\SqlQueryException
	 */
	public static function deleteByProjectIdAndUserId(int $projectId, int $userId): void
	{
		$tableName = static::getTableName();

		$connection = Main\Application::getConnection();
		$connection->query("
			DELETE FROM {$tableName}
			WHERE PROJECT_ID = {$projectId} AND USER_ID = {$userId}
		");
	}

	public static function getSelectExpression(int $userId, int $option): string
	{
		$tableName = static::getTableName();

		return "
			IF(
				EXISTS(
					SELECT 'x'
					FROM {$tableName}
					WHERE
						PROJECT_ID = %s
						AND USER_ID = {$userId}
						AND OPTION_CODE = {$option}
				),
				'Y',
				'N'
			)
		";
	}
}