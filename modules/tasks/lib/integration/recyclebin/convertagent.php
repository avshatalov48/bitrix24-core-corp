<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Integration\Recyclebin;

use Bitrix\Main\Loader;
use Bitrix\Recyclebin\Internals\Models\RecyclebinDataTable;
use Bitrix\Recyclebin\Internals\Models\RecyclebinTable;
use Bitrix\Tasks\Internals\TaskTable;
use Bitrix\Tasks\Update\AgentInterface;
use Bitrix\Tasks\Update\AgentTrait;

class ConvertAgent implements AgentInterface
{
	use AgentTrait;

	public const LIMIT = 500;

	private static $processing = false;

	/**
	 * @return bool
	 */
	public static function isProceed(): bool
	{
		return (int) \COption::GetOptionString('tasks', 'task_zombie_convert', 0) === 1;
	}

	public static function execute(): string
	{
		if (self::$processing)
		{
			return static::getAgentName();
		}

		self::$processing = true;

		$agent = new self();
		$res = $agent->run();

		self::$processing = false;

		return $res;
	}

	public function __construct()
	{

	}

	/**
	 * @return string
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function run()
	{
		if (!Loader::includeModule('recyclebin'))
		{
			$this->convertDone();
			return '';
		}

		$tasks = $this->getList();
		if (empty($tasks))
		{
			$this->convertDone();
			return '';
		}

		$taskIds = array_keys($tasks);
		$recycles = $this->getRecyclesList($taskIds);

		foreach ($tasks as $taskId => $task)
		{
			if (!array_key_exists($taskId, $recycles))
			{
				continue;
			}

			$this->addToRecycle((int)$recycles[$taskId], $task);
		}

		$this->deleteFromTasks($taskIds);

		return static::getAgentName();
	}

	/**
	 * @param int $recycleId
	 * @param array $task
	 */
	private function addToRecycle(int $recycleId, array $task): void
	{
		RecyclebinDataTable::add([
			'RECYCLEBIN_ID' => $recycleId,
			'ACTION' => 'TASK',
			'DATA' => serialize($task),
		]);
	}

	/**
	 * @param array $taskIds
	 */
	private function deleteFromTasks(array $taskIds): void
	{
		TaskTable::deleteList([
			'@ID' => $taskIds,
		]);
	}

	/**
	 *
	 */
	private function convertDone(): void
	{
		\COption::RemoveOption('tasks', 'task_zombie_convert');
	}

	/**
	 * @param array $taskIds
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getRecyclesList(array $taskIds): array
	{
		$res = RecyclebinTable::getList([
			'select' => ['ID', 'ENTITY_ID'],
			'filter' => [
				'=ENTITY_TYPE' => Manager::TASKS_RECYCLEBIN_ENTITY,
				'=MODULE_ID' => Manager::MODULE_ID,
				'@ENTITY_ID' => $taskIds,
			]
		])->fetchAll();

		$recycles = [];
		foreach ($res as $row)
		{
			$recycles[$row['ENTITY_ID']] = $row['ID'];
		}

		return $recycles;
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getList(): array
	{
		$tasks = \Bitrix\Tasks\Internals\TaskTable::getList([
			'filter' => [
				'=ZOMBIE' => 'Y',
			],
			'order' => [
				'ID' => 'ASC'
			],
			'limit' => self::LIMIT
		])->fetchCollection();

		$list = [];
		foreach ($tasks as $task)
		{
			$list[$task->getId()] = $task->toArray();
		}

		return $list;
	}
}