<?php

namespace Bitrix\Tasks\Update;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Internals\EO_Task_Collection;
use Bitrix\Tasks\Internals\Log\Log;
use Bitrix\Tasks\Internals\Task\ScenarioTable;
use Bitrix\Tasks\Internals\TaskTable;

class SyncTaskScenario
{
	public const CURSOR_KEY = 'sync_tasks_scenario_cursor';
	public const LIMIT = 500;
	private static bool $processing = false;

	private function __construct()
	{

	}

	public static function execute(): string
	{
		if (self::$processing)
		{
			return self::getAgentName();
		}

		self::$processing = true;

		$agent = new self();
		$res = $agent->run();

		self::$processing = false;

		return $res;
	}

	private function run(): string
	{
		if (!Loader::includeModule('tasks'))
		{
			return '';
		}

		try
		{
			// fetch tasks to sync
			$cursor = $this->getCursor();
			$tasks = $this->getList($cursor);
			$latestTaskId = 0;
			// insert default scenario
			foreach ($tasks as $task)
			{
				ScenarioTable::insertIgnore($task->getId(), [ScenarioTable::SCENARIO_DEFAULT]);
				$latestTaskId = $task->getId();
			}

			if ($latestTaskId)
			{
				$this->setCursor($latestTaskId);
			}

			if ($tasks->count() < self::LIMIT)
			{
				// sync is over, some clean up!
				Option::delete('tasks', ['name' => self::CURSOR_KEY]);
				Option::delete('tasks', ['name' => 'task_sync_in_progress']);
				return '';
			}
		}
		catch (\Exception $e)
		{
			(new Log())->collect('Unable to sync task scenario. '.$e->getMessage());
			return '';
		}

		return self::getAgentName();
	}

	/**
	 * @param int $cursor
	 * @return EO_Task_Collection
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function getList(int $cursor): \Bitrix\Tasks\Internals\EO_Task_Collection
	{
		return TaskTable::getList([
			'select' => ['ID'],
			'filter' => ['<=ID' => $cursor],
			'order' => ['ID' => 'DESC'],
			'limit' => self::LIMIT
		])->fetchCollection();
	}

	/**
	 * @return int
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function getLatestTaskId(): int
	{
		$latestTask = TaskTable::getList([
			'select' => ['ID'],
			'order' => ['ID' => 'DESC'],
			'limit' => 1,
		])->fetchObject();
		return $latestTask ? $latestTask->getId() : 0;
	}

	/**
	 * @return int
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function getCursor(): int
	{
		$cursor = Option::get('tasks', self::CURSOR_KEY, 'n/a');
		if ($cursor !== 'n/a')
		{
			return $cursor;
		}
		// cursor is not set, return latest task id
		return $this->getLatestTaskId();
	}

	/**
	 * @param int $cursor
	 * @return void
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	private function setCursor(int $cursor): void
	{
		Option::set('tasks', self::CURSOR_KEY, $cursor);
	}

	/**
	 * @return string
	 */
	private static function getAgentName(): string
	{
		return self::class . "::execute();";
	}
}