<?php
namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Internals\TaskDataManager;

/**
 * Class SearchIndexTable
 *
 * @package Bitrix\Tasks\Internals\Task
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_SearchIndex_Query query()
 * @method static EO_SearchIndex_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_SearchIndex_Result getById($id)
 * @method static EO_SearchIndex_Result getList(array $parameters = [])
 * @method static EO_SearchIndex_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\Task\EO_SearchIndex createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Task\EO_SearchIndex_Collection createCollection()
 * @method static \Bitrix\Tasks\Internals\Task\EO_SearchIndex wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Task\EO_SearchIndex_Collection wakeUpCollection($rows)
 */
class SearchIndexTable extends TaskDataManager
{
	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_tasks_search_index';
	}

	/**
	 * @return string
	 */
	public static function getClass()
	{
		return get_called_class();
	}

	/**
	 * @return array
	 */
	public static function getMap()
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
			'MESSAGE_ID' => [
				'data_type' => 'integer',
				'required' => false,
				'default' => 0,
			],
			'SEARCH_INDEX' => [
				'data_type' => 'text',
				'required' => false,
			],
		];
	}

	/**
	 * @param int $taskId
	 * @param int $messageId
	 * @param string $searchIndex
	 * @return bool
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws \Exception
	 */
	public static function set(int $taskId, int $messageId, string $searchIndex): bool
	{
		$messageId = ($messageId ?: 0);
		$searchIndex = trim($searchIndex);

		if ($taskId <= 0 || empty($searchIndex))
		{
			return false;
		}

		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();
		$searchIndex = $sqlHelper->forSql($searchIndex);

		$row = static::getList([
			'select' => ['ID', 'SEARCH_INDEX'],
			'filter' => [
				'TASK_ID' => $taskId,
				'MESSAGE_ID' => $messageId,
			],
		])->fetch();

		if (!$row)
		{
			static::add([
				'TASK_ID' => $taskId,
				'MESSAGE_ID' => $messageId,
				'SEARCH_INDEX' => $searchIndex,
			]);

			return true;
		}

		if ($searchIndex === $row['SEARCH_INDEX'])
		{
			return true;
		}

		static::update(
			['ID' => $row['ID']],
			['SEARCH_INDEX' => $searchIndex],
		);

		return true;
	}

	/**
	 * @param int $taskId
	 * @param int $messageId
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws \Exception
	 */
	public static function deleteByTaskAndMessageIds(int $taskId, int $messageId): void
	{
		$index = static::getList([
			'select' => ['ID'],
			'filter' => [
				'TASK_ID' => $taskId,
				'MESSAGE_ID' => $messageId,
			],
		])->fetch();

		if ($index)
		{
			static::delete($index);
		}
	}
}