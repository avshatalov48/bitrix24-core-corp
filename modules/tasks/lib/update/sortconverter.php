<?php

namespace Bitrix\Tasks\Update;

use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Update\Stepper;
use Bitrix\Tasks\Internals\Log\LogFacade;
use Bitrix\Tasks\Internals\Task\SortingTable;

final class SortConverter extends Stepper
{
	public static $moduleId = 'tasks';
	private const LIMIT = 1000;

	private int $lastId;
	private array $items = [];

	public static function getTitle(): string
	{
		return Loc::getMessage('TASKS_SORT_CONVERTER_TITLE');
	}

	public function execute(array &$option): bool
	{
		$this
			->setLastId($option['lastId'] ?? 0)
			->setItems();

		if (empty($this->items))
		{
			return self::FINISH_EXECUTION;
		}

		$this
			->moveItems()
			->updateLastId()
			->setOptions($option);

		return self::CONTINUE_EXECUTION;
	}

	private function setItems(): self
	{
		$connection = Application::getConnection();
		$limit = self::LIMIT;
		$this->items = [];
		try
		{
			$query = $connection->query("
			select * from b_tasks_sorting where ID > {$this->lastId} order by ID asc limit {$limit}
		");
		}
		catch (SqlQueryException $exception)
		{
			LogFacade::logThrowable($exception);
			return $this;
		}


		$this->items = $query->fetchAll();

		return $this;
	}

	private function setLastId(int $id = 0): self
	{
		$this->lastId = $id;
		return $this;
	}

	private function updateLastId(): self
	{
		$this->lastId = max(array_map('intval', array_column($this->items, 'ID')));
		return $this;
	}

	private function moveItems(): self
	{
		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();
		$values = [];
		foreach ($this->items as $item)
		{
			$values[]= "({$item['TASK_ID']}, {$item['SORT']}, {$item['USER_ID']}, {$item['GROUP_ID']}, {$item['PREV_TASK_ID']}, {$item['NEXT_TASK_ID']})";
		}

		$values = implode(', ', $values);

		$fields = '(TASK_ID, SORT, USER_ID, GROUP_ID, PREV_TASK_ID, NEXT_TASK_ID)';

		$query = $helper->getInsertIgnore(
			SortingTable::getTableName(),
			" {$fields}",
			" VALUES {$values}"
		);

		try
		{
			$connection->query($query);
		}
		catch (SqlQueryException $exception)
		{
			LogFacade::logThrowable($exception);
		}

		return $this;
	}

	private function setOptions(array &$options): self
	{
		$options['lastId'] = $this->lastId;
		return $this;
	}
}