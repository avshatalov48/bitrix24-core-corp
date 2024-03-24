<?php
namespace Bitrix\Tasks\Rest\Controllers\Action;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Search;
use Bitrix\Main\SystemException;
use Bitrix\Main\Text\Emoji;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Tasks\Internals\SearchIndex;
use Bitrix\Tasks\Internals\Task\SearchIndexTable;
use Bitrix\Tasks\Provider\Exception\InvalidGroupByException;
use Bitrix\Tasks\Provider\Exception\TaskListException;
use Bitrix\Tasks\Provider\TaskList;
use Bitrix\Tasks\Provider\TaskQuery;
use Bitrix\Tasks\Slider\Path\PathMaker;
use Bitrix\Tasks\Slider\Path\TaskPathMaker;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit\FilterLimit;
use TasksException;

class SearchAction extends Search\SearchAction
{
	private int $maxSearchSize = 10;

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws TasksException
	 * @restMethod tasks.task.search
	 */
	public function provideData($searchQuery, array $options = null, PageNavigation $pageNavigation = null): ?array
	{
		$result = [];

		if (FilterLimit::isLimitExceeded() || !$this->isSearchQueryValid($searchQuery))
		{
			return $result;
		}

		try
		{
			$tasksBySearch = $this->getTasksBySearch($searchQuery);
		}
		catch (TaskListException|InvalidGroupByException $exception)
		{
			$this->addError(Error::createFromThrowable($exception));
			return null;
		}

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
	 * @throws SystemException
	 * @throws ObjectPropertyException
	 */
	protected function provideLimits($searchQuery, array $options = null): array
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
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws TaskListException
	 * @throws InvalidGroupByException
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
	 * @throws TaskListException
	 * @throws InvalidGroupByException
	 */
	private function getTasksWithSearchInBody(string $searchQuery): array
	{
		return $this->runGetList($searchQuery, 'SEARCH_TASK_ONLY');
	}

	/**
	 * @throws TaskListException
	 * @throws InvalidGroupByException
	 */
	private function getTasksWithSearchInComments(string $searchQuery): array
	{
		return $this->runGetList($searchQuery, 'SEARCH_COMMENT_ONLY');
	}

	/**
	 * @throws TaskListException
	 * @throws InvalidGroupByException
	 */
	private function runGetList(string $searchQuery, string $searchOption): array
	{
		$query = (new TaskQuery($this->getCurrentUser()->getId()))
			->setParam($searchOption, 'Y')
			->setSelect(['ID', 'TITLE'])
			->addWhere('*FULL_SEARCH_INDEX', SearchIndex::prepareStringToSearch($searchQuery))
			->setGroupBy('ID')
			->setOrder(['ID' => 'ASC'])
			->setLimit($this->maxSearchSize);

		$tasks = (new TaskList())->getList($query);

		return array_map(static function (array $task): array {
			$task['MESSAGE_ID'] = 0;
			$task['TITLE'] = Emoji::decode($task['TITLE']);
			return $task;
		}, $tasks);
	}

	private function clearDuplicates(array $array, array $duplicates): array
	{
		return array_diff_key($array, $duplicates);
	}

	/**
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

	private function getPathForTask(int $taskId): string
	{
		return (new TaskPathMaker($taskId, PathMaker::DEFAULT_ACTION, $this->getCurrentUser()->getId()))
			->makeEntityPath();
	}

	private function getPathForTaskComment(int $taskId, int $commentId): string
	{
		return (new TaskPathMaker($taskId, PathMaker::DEFAULT_ACTION, $this->getCurrentUser()->getId()))
			->setQueryParams("MID={$commentId}#com{$commentId}")
			->makeEntityPath();
	}

	private function isSearchQueryValid(string $searchQuery): bool
	{
		return SearchIndex::prepareStringToSearch($searchQuery) !== '';
	}
}
