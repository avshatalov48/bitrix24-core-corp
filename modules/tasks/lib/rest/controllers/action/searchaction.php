<?php
namespace Bitrix\Tasks\Rest\Controllers\Action;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Search;
use Bitrix\Main\SystemException;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Tasks\Exception;
use Bitrix\Tasks\Internals\SearchIndex;
use Bitrix\Tasks\Internals\Task\SearchIndexTable;

use CComponentEngine;
use CTasks;
use CTasksTools;
use TasksException;

/**
 * Class Search
 * @package Bitrix\Tasks\Rest\Controllers\Action
 */
class SearchAction extends Search\SearchAction
{
	private static $taskPathTemplate = '';

	/**
	 * BX.ajax.runAction("tasks.task.search", {data: {searchQuery: "text"}});
	 *
	 * @param string $searchQuery
	 * @param array|null $options
	 * @param PageNavigation|null $pageNavigation
	 * @return array|Search\ResultItem[]
	 * @throws ArgumentException
	 * @throws ArgumentNullException
	 * @throws ArgumentOutOfRangeException
	 * @throws SystemException
	 * @throws TasksException
	 */
	public function provideData($searchQuery, array $options = null, PageNavigation $pageNavigation = null)
	{
		$result = [];

		$userId = $this->getCurrentUser()->getId();
		$tasksBySearch = $this->getTasksBySearch($searchQuery, $userId);

		foreach ($tasksBySearch as $key => $task)
		{
			$taskId = $task['ID'];
			$messageId = $task['MESSAGE_ID'];

			$path = ($messageId? $this->getPathForTaskComment($taskId, $messageId) : $this->getPathForTask($taskId));

			$resultItem = new Search\ResultItem($task['TITLE'], $path, $taskId);
			$resultItem
				->setModule('tasks')
				->setType('TASK')
			;

			$result[] = $resultItem;
		}

		return $result;
	}

	/**
	 * @param $searchQuery
	 * @param $userId
	 * @return array
	 * @throws ArgumentException
	 * @throws ArgumentNullException
	 * @throws ArgumentOutOfRangeException
	 * @throws SystemException
	 * @throws TasksException
	 */
	private static function getTasksBySearch($searchQuery, $userId)
	{
		$result = [];

		$operator = (($isFullTextIndexEnabled = SearchIndexTable::isFullTextIndexEnabled())? '*' : '*%');
		$searchValue = SearchIndex::prepareStringToSearch($searchQuery, $isFullTextIndexEnabled);

		$select = ['ID', 'TITLE', 'MESSAGE_ID'];
		$order = [
			'MESSAGE_ID' => 'ASC',
			'ID' => 'ASC'
		];
		$filter = [
			'::SUBFILTER-FULL_SEARCH_INDEX' => [$operator . 'FULL_SEARCH_INDEX' => $searchValue]
		];
		$params = [
			'USER_ID' => $userId,
			'MAKE_ACCESS_FILTER' => true
		];

		$taskDbResult = CTasks::GetList($order, $filter, $select, $params, []);
		while ($task = $taskDbResult->Fetch())
		{
			$result[] = $task;
		}

		return $result;
	}

	/**
	 * @return string
	 */
	private static function getTaskPathTemplate()
	{
		if (self::$taskPathTemplate)
		{
			return self::$taskPathTemplate;
		}

		$defaultPathTemplate = '/company/personal/user/#user_id#/tasks/task/view/#task_id#/';

		try
		{
			$pathTemplate = CTasksTools::GetOptionPathTaskUserEntry(SITE_ID, $defaultPathTemplate);
		}
		catch (Exception $exception)
		{
			$pathTemplate = $defaultPathTemplate;
		}

		$search = ['#USER_ID#', '#TASK_ID#'];
		$replace = ['#user_id#', '#task_id#'];
		$pathTemplate = str_replace($search, $replace, $pathTemplate);

		self::$taskPathTemplate = $pathTemplate;

		return $pathTemplate;
	}

	/**
	 * @return string
	 */
	private static function getTaskCommentPathTemplate()
	{
		return self::getTaskPathTemplate() . '?MID=#comment_id##com#comment_id#';
	}

	/**
	 * @param $taskId
	 * @return string
	 */
	private function getPathForTask($taskId)
	{
		$userId = $this->getCurrentUser()->getId();
		$pathTemplate = self::getTaskPathTemplate();

		return CComponentEngine::MakePathFromTemplate($pathTemplate, ['user_id' => $userId, 'task_id' => $taskId]);
	}

	/**
	 * @param $taskId
	 * @param $commentId
	 * @return string
	 */
	private function getPathForTaskComment($taskId, $commentId)
	{
		$userId = $this->getCurrentUser()->getId();
		$pathTemplate = self::getTaskCommentPathTemplate();

		return CComponentEngine::MakePathFromTemplate($pathTemplate, [
			'user_id' => $userId,
			'task_id' => $taskId,
			'comment_id' => $commentId
		]);
	}
}