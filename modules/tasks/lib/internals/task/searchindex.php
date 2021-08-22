<?php
namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Db\SqlQueryException;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

/**
 * Class SearchIndexTable
 *
 * @package Bitrix\Tasks\Internals\Task
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_SearchIndex_Query query()
 * @method static EO_SearchIndex_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_SearchIndex_Result getById($id)
 * @method static EO_SearchIndex_Result getList(array $parameters = array())
 * @method static EO_SearchIndex_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\Task\EO_SearchIndex createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Task\EO_SearchIndex_Collection createCollection()
 * @method static \Bitrix\Tasks\Internals\Task\EO_SearchIndex wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Task\EO_SearchIndex_Collection wakeUpCollection($rows)
 */
class SearchIndexTable extends DataManager
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
				'autocomplete' => true
			],

			'TASK_ID' => [
				'data_type' => 'integer',
				'required' => true
			],

			'MESSAGE_ID' => [
				'data_type' => 'integer',
				'required' => false,
				'default' => 0
			],

			'SEARCH_INDEX' => [
				'data_type' => 'text',
				'required' => false
			]
		];
	}

	/**
	 * @param $taskId
	 * @param $messageId
	 * @param $searchIndex
	 * @return bool
	 * @throws SqlQueryException
	 */
	public static function set($taskId, $messageId, $searchIndex)
	{
		$taskId = ($taskId? intval($taskId) : 0);
		$messageId = ($messageId? intval($messageId) : 0);
		$searchIndex = ($searchIndex? trim($searchIndex) : '');

		if ($taskId <= 0 || empty($searchIndex))
		{
			return false;
		}

		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$insertFields = [
			"TASK_ID" => $taskId,
			"MESSAGE_ID" => $messageId,
			"SEARCH_INDEX" => $sqlHelper->forSql($searchIndex)
		];

		$updateFields = [
			"SEARCH_INDEX" => $sqlHelper->forSql($searchIndex)
		];

		$merge = $sqlHelper->prepareMerge(static::getTableName(), ["ID"], $insertFields, $updateFields);

		$connection->query($merge[0]);

		return true;
	}

	/**
	 * @param $taskId
	 * @param $messageId
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws \Exception
	 */
	public static function deleteByTaskAndMessageIds($taskId, $messageId)
	{
		$index = static::getList([
			'select' => ['ID'],
			'filter' => ['TASK_ID' => $taskId, 'MESSAGE_ID' => $messageId]
		])->fetch();

		if ($index)
		{
			static::delete($index);
		}
	}

	/**
	 * @return bool
	 * @throws ArgumentException
	 * @throws SystemException
	 * @throws ArgumentNullException
	 * @throws ArgumentOutOfRangeException
	 */
	public static function isFullTextIndexEnabled()
	{
		return static::getEntity()->fullTextIndexEnabled("SEARCH_INDEX");
	}
}