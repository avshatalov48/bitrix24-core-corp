<?php
namespace Bitrix\Tasks\Rest\Controllers\Action;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Search;
use Bitrix\Main\SystemException;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Tasks\Exception;
use Bitrix\Tasks\Internals\SearchIndex;
use Bitrix\Tasks\Internals\Task\SearchIndexTable;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit\FilterLimit;

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
	private $maxSearchSize = 10;

	/**
	 * BX.ajax.runAction("tasks.task.search", {data: {searchQuery: "text"}});
	 *
	 * @param string $searchQuery
	 * @param array|null $options
	 * @param PageNavigation|null $pageNavigation
	 * @return array|Search\ResultItem[]
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws TasksException
	 */
	public function provideData($searchQuery, array $options = null, PageNavigation $pageNavigation = null): array
	{
		$result = [];

		if (FilterLimit::isLimitExceeded() || !$this->isSearchQueryValid($searchQuery))
		{
			return $result;
		}

		$tasksBySearch = $this->getTasksBySearch($searchQuery);
		foreach ($tasksBySearch as $task)
		{
			$taskId = (int)$task['ID'];
			$messageId = (int)$task['MESSAGE_ID'];

			$path = ($messageId ? $this->getPathForTaskComment($taskId, $messageId) : $this->getPathForTask($taskId));

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
	 * @param array|null $options
	 * @return array|Search\ResultLimit
	 * @throws SystemException
	 * @throws ObjectPropertyException
	 */
	protected function provideLimits($searchQuery, array $options = null)
	{
		if (FilterLimit::isLimitExceeded())
		{
			$type = 'TASK';
			$info = FilterLimit::prepareStubInfo([
				'TITLE' => Loc::getMessage("TASKS_CONTROLLER_SEARCH_ACTION_TASKS_LIMIT_EXCEEDED_TITLE"),
				'CONTENT' => Loc::getMessage("TASKS_CONTROLLER_SEARCH_ACTION_TASKS_LIMIT_EXCEEDED"),
				'GLOBAL_SEARCH' => true,
			]);

			$resultLimit = new Search\ResultLimit($type, $info['TITLE'], $info['DESCRIPTION']);
			$resultLimit->setButtons($info['BUTTONS']);

			return [$resultLimit];
		}

		return [];
	}

	/**
	 * @param string $searchQuery
	 * @return array
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws TasksException
	 */
	private function getTasksBySearch(string $searchQuery): array
	{
		$tasks = $this->getTasksWithSearchInBody($searchQuery);
		if (count($tasks) < $this->maxSearchSize)
		{
			$tasksWithSearchInComments = $this->getTasksWithSearchInComments($searchQuery);
			$tasksWithSearchInComments = $this->clearDuplicates($tasksWithSearchInComments, $tasks);
			$tasksWithSearchInComments = $this->fillMessageIds($tasksWithSearchInComments);

			$tasks = array_merge($tasks, $tasksWithSearchInComments);
		}

		return array_slice($tasks, 0, 20);
	}

	/**
	 * @param string $searchQuery
	 * @return array
	 * @throws TasksException
	 */
	private function getTasksWithSearchInBody(string $searchQuery): array
	{
		return $this->runGetList($searchQuery, ['SEARCH_TASK_ONLY' => 'Y']);
	}

	/**
	 * @param string $searchQuery
	 * @return array
	 * @throws TasksException
	 */
	private function getTasksWithSearchInComments(string $searchQuery): array
	{
		return $this->runGetList($searchQuery, ['SEARCH_COMMENT_ONLY' => 'Y']);
	}

	/**
	 * @param string $searchQuery
	 * @param array $filterParams
	 * @return array
	 * @throws TasksException
	 */
	private function runGetList(string $searchQuery, array $filterParams): array
	{
		$result = [];

		$select = ['ID', 'TITLE'];
		$order = [
			'ID' => 'ASC',
		];
		$filter = [
			'::SUBFILTER-FULL_SEARCH_INDEX' => [
				'*FULL_SEARCH_INDEX' => SearchIndex::prepareStringToSearch($searchQuery),
			],
		];
		$params = [
			'USER_ID' => $this->getCurrentUser()->getId(),
			'NAV_PARAMS' => [
				'nTopCount' => $this->maxSearchSize,
			],
			'FILTER_PARAMS' => $filterParams,
		];

		$taskDbResult = CTasks::GetList($order, $filter, $select, $params);
		while ($task = $taskDbResult->Fetch())
		{
			$task['MESSAGE_ID'] = 0;
			$task['TITLE'] = \Bitrix\Main\Text\Emoji::decode($task['TITLE']);
			$result[$task['ID']] = $task;
		}

		return $result;
	}

	private function clearDuplicates(array $array, array $duplicates): array
	{
		return array_diff_key($array, $duplicates);
	}

	/**
	 * @param array $tasks
	 * @return array
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	private function fillMessageIds(array $tasks): array
	{
		$list = SearchIndexTable::getList([
			'select' => ['TASK_ID', 'MESSAGE_ID'],
			'filter' => [
				'!MESSAGE_ID' => 0,
				'TASK_ID' => array_keys($tasks),
			],
		])->fetchAll();

		$result = $tasks;
		foreach ($list as $item)
		{
			$taskId = $item['TASK_ID'];
			if (array_key_exists($taskId, $result))
			{
				$result[$taskId]['MESSAGE_ID'] = $item['MESSAGE_ID'];
			}
		}

		return $result;
	}

	/**
	 * @return string
	 */
	private function getTaskPathTemplate(): string
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
	private function getTaskCommentPathTemplate(): string
	{
		return $this->getTaskPathTemplate().'?MID=#comment_id##com#comment_id#';
	}

	/**
	 * @param int $taskId
	 * @return string
	 */
	private function getPathForTask(int $taskId): string
	{
		$userId = $this->getCurrentUser()->getId();
		$pathTemplate = $this->getTaskPathTemplate();

		return CComponentEngine::MakePathFromTemplate($pathTemplate, ['user_id' => $userId, 'task_id' => $taskId]);
	}

	/**
	 * @param int $taskId
	 * @param int $commentId
	 * @return string
	 */
	private function getPathForTaskComment(int $taskId, int $commentId): string
	{
		$userId = $this->getCurrentUser()->getId();
		$pathTemplate = $this->getTaskCommentPathTemplate();

		return CComponentEngine::MakePathFromTemplate($pathTemplate, [
			'user_id' => $userId,
			'task_id' => $taskId,
			'comment_id' => $commentId,
		]);
	}

	/**
	 * @param string $searchQuery
	 * @return bool
	 */
	private function isSearchQueryValid(string $searchQuery): bool
	{
		return SearchIndex::prepareStringToSearch($searchQuery) !== '';
	}
}