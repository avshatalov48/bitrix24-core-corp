<?php
namespace Bitrix\Tasks\Rest\Controllers\Action;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
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
	public function provideData($searchQuery, array $options = null, PageNavigation $pageNavigation = null): array
	{
		$result = [];

		if (FilterLimit::isLimitExceeded() || !$this->isSearchQueryValid($searchQuery))
		{
			return $result;
		}

		$userId = $this->getCurrentUser()->getId();
		$tasksBySearch = $this->getTasksBySearch($searchQuery, $userId);

		foreach ($tasksBySearch as $key => $task)
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
	 * @param int $userId
	 * @return array
	 * @throws ArgumentException
	 * @throws ArgumentNullException
	 * @throws ArgumentOutOfRangeException
	 * @throws SystemException
	 * @throws TasksException
	 */
	private function getTasksBySearch(string $searchQuery, int $userId): array
	{
		$result = [];

		$operator = (($isFullTextIndexEnabled = SearchIndexTable::isFullTextIndexEnabled())? '*' : '*%');
		$searchValue = SearchIndex::prepareStringToSearch($searchQuery, $isFullTextIndexEnabled);

		$select = ['ID', 'TITLE', 'MESSAGE_ID'];
		$order = [
			'MESSAGE_ID' => 'ASC',
			'ID' => 'ASC',
		];
		$filter = [
			'::SUBFILTER-FULL_SEARCH_INDEX' => [$operator.'FULL_SEARCH_INDEX' => $searchValue],
		];
		$params = [
			'USER_ID' => $userId,
			'NAV_PARAMS' => [
				'nTopCount' => 20,
			],
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
		return trim($searchQuery) !== '';
	}
}