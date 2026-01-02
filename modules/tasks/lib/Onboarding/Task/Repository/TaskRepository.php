<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Task\Repository;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Validation\ValidationService;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\Internals\TaskTable;
use Bitrix\Tasks\Onboarding\Task\TaskRepositoryInterface;

class TaskRepository implements TaskRepositoryInterface
{
	private static array $countOnePersonTasks = [];
	private static array $createdAfterTasksCount = [];

	protected ValidationService $validationService;

	public function __construct()
	{
		$this->init();
	}

	public function getOnePersonTasksCount(int $userId): int
	{
		if (isset(static::$countOnePersonTasks[$userId]))
		{
			return static::$countOnePersonTasks[$userId];
		}

		if ($userId <= 0)
		{
			return 0;
		}

		$statusFilter = Query::filter()
			->logic(ConditionTree::LOGIC_OR)
			->whereIn('STATUS', Status::getInWorkStatuses());

		$row = TaskTable::query()
			->setSelect([Query::expr('COUNT')->count('ID')])
			->where('CREATED_BY', $userId)
			->where('RESPONSIBLE_ID', $userId)
			->where($statusFilter)
			->exec()
			->fetch();

		$count = (int)($row['COUNT'] ?? 0);

		static::$countOnePersonTasks[$userId] = $count;

		return static::$countOnePersonTasks[$userId];
	}

	public function getCreatedAfterTasksCount(int $userId, DateTime $date): int
	{
		if (isset(static::$createdAfterTasksCount[$userId][$date->toString()]))
		{
			return static::$createdAfterTasksCount[$userId][$date->toString()];
		}

		if ($userId <= 0)
		{
			return 0;
		}

		$statusFilter = Query::filter()
			->logic(ConditionTree::LOGIC_OR)
			->whereIn('STATUS', Status::getInWorkStatuses());

		$row = TaskTable::query()
			->setSelect([Query::expr('COUNT')->count('ID')])
			->where('CREATED_BY', $userId)
			->where('CREATED_DATE', '>=', $date)
			->where($statusFilter)
			->exec()
			->fetch();

		$count = (int)($row['COUNT'] ?? 0);

		static::$createdAfterTasksCount[$userId][$date->toString()] = $count;

		return static::$createdAfterTasksCount[$userId][$date->toString()];
	}

	protected function init(): void
	{
		$this->validationService = ServiceLocator::getInstance()->get('main.validation.service');
	}
}