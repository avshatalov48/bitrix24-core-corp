<?php

namespace Bitrix\Tasks\Flow\Controllers\Trait;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Tasks\Flow\Controllers\Task\Cache\TaskCountCache;
use Bitrix\Tasks\Provider\Exception\TaskListException;
use Bitrix\Tasks\Provider\TaskList;
use Bitrix\Tasks\Provider\TaskQuery;
use Closure;

trait TaskTrait
{
	use UserTrait;
	use ControllerTrait;

	protected Converter $converter;
	protected TaskList $provider;
	protected int $userId;

	/**
	 * @throws TaskListException
	 */
	private function getTaskList(
		array $select,
		array $filter,
		PageNavigation $pageNavigation,
		array $order,
		Closure $modifier
	): array
	{
		$query = (new TaskQuery($this->userId))
			->skipAccessCheck()
			->setSelect($select)
			->setWhere($filter)
			->setOrder($order)
			->setOffset($pageNavigation->getOffset())
			->setLimit($pageNavigation->getLimit());

		$pageNavigation->getLimit();

		$tasks = $this->provider->getList($query);

		foreach ($tasks as $i => &$task)
		{
			$task['SERIAL'] = $pageNavigation->getOffset() + $i + 1;

			$modifier($task);
		}

		return
		[
			'tasks' => $this->converter->process($this->formatTasks($tasks)),
			'totalCount' => $this->getTaskTotalCount($filter, $pageNavigation),
		];
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	private function getTaskCount(array $filter): int
	{
		$query = (new TaskQuery($this->userId))
			->skipAccessCheck()
			->setWhere($filter);

		return $this->provider->getCount($query);
	}

	private function formatTasks(array $tasks): array
	{
		$creatorIds = array_column($tasks, 'CREATED_BY');
		$responsibleIds = array_column($tasks, 'RESPONSIBLE_ID');

		$memberIds = array_merge($creatorIds, $responsibleIds);
		$members = $this->getUsers(...$memberIds);

		$response = [];
		foreach ($tasks as $task)
		{
			$response[] = [
				'SERIAL' => $task['SERIAL'],
				'CREATOR' => $members[$task['CREATED_BY']],
				'RESPONSIBLE' => $members[$task['RESPONSIBLE_ID']],
				'TIME_IN_STATUS' => $task['TIME_IN_STATUS'],
			];
		}

		return $response;
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	protected function getTaskTotalCount(
		array $filter,
		PageNavigation $pageNavigation
	): int
	{
		$cache = new TaskCountCache();

		$this->invalidateCacheIfFirstPage($pageNavigation, $cache, $filter);

		$cachedTotalCount = $cache->get($filter);
		if ($cachedTotalCount !== null)
		{
			return $cachedTotalCount;
		}

		$totalCount = $this->getTaskCount($filter);
		$cache->store($filter, $totalCount);

		return $totalCount;
	}

	private function invalidateCacheIfFirstPage(
		PageNavigation $pageNavigation,
		TaskCountCache $cache,
		array $filter
	): void
	{
		if ($pageNavigation->getCurrentPage() === 1)
		{
			$cache->invalidate($filter);
		}
	}

	protected function init(): void
	{
		parent::init();

		$this->userId = (int)CurrentUser::get()->getId();
		$this->provider = new TaskList();
		$this->converter = new Converter(Converter::OUTPUT_JSON_FORMAT);
	}
}