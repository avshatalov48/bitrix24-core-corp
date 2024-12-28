<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Stepper\Filler;

use Bitrix\Forum\MessageTable;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Flow\Integration\AI\TaskCollection;
use Bitrix\Tasks\Internals\Task\LogTable;

trait TaskCollectionTrait
{
	abstract protected function getDateInterval(): int;

	protected function getLifeTimeSum(TaskCollection $tasks): float
	{
		$sum = 0;
		$interval = $this->getDateInterval();

		foreach ($tasks as $task)
		{
			$closedDate = $task['CLOSED_DATE'] ?? null;
			if (!$closedDate instanceof DateTime)
			{
				continue;
			}

			$closedDate = $closedDate->getTimestamp();

			$createdDate = $this->getTaskCreatedDate($task);
			if (!$createdDate instanceof DateTime)
			{
				continue;
			}

			$createdDate = $createdDate->getTimestamp();

			$sum += ($closedDate - $createdDate) / $interval;
		}

		return $sum;
	}

	protected function getDeadlinePeriodSum(TaskCollection $tasks): float
	{
		$period = 0;
		$interval = $this->getDateInterval();

		foreach ($tasks as $task)
		{
			$closedDate = $task['CLOSED_DATE'];
			if (!$closedDate instanceof DateTime)
			{
				continue;
			}

			$closedDate = $closedDate->getTimestamp();

			/** @var DateTime $deadline */
			$deadline = $task['DEADLINE'];
			if (!$deadline instanceof DateTime)
			{
				continue;
			}

			$deadline = $deadline->getTimestamp();

			$period += ($closedDate - $deadline) / $interval;
		}

		return $period;
	}

	protected function getDistributionTimeSum(TaskCollection $tasks): float
	{
		$sum = 0;
		$interval = $this->getDateInterval();

		foreach ($tasks as $task)
		{
			$taskId = (int)$task['ID'];

			$createdDate = $this->getTaskCreatedDate($task);
			if (!$createdDate instanceof DateTime)
			{
				continue;
			}

			$createdDate = $createdDate->getTimestamp();

			$distributionDate = $this->distributionTimes[$taskId] ?? null;
			if (!$distributionDate instanceof DateTime)
			{
				continue;
			}

			$distributionDate = $distributionDate->getTimestamp();

			$sum += ($distributionDate - $createdDate) / $interval;
		}

		return $sum;
	}

	protected function getAtWorkTimeSum(TaskCollection $tasks): float
	{
		$atWorkTimeSum = 0;
		$interval = $this->getDateInterval();

		if (!isset($this->distributionTimes))
		{
			return $atWorkTimeSum;
		}

		foreach ($this->distributionTimes as $taskId => $distributionTime)
		{
			$task = $tasks->getById($taskId);
			if (null === $task)
			{
				continue;
			}

			$closedDate = $task['CLOSED_DATE'];
			if (!$closedDate instanceof DateTime)
			{
				continue;
			}

			$closedDate = $closedDate->getTimestamp();
			$distributionTime = $distributionTime->getTimestamp();

			$atWorkTimeSum += ($closedDate - $distributionTime) / $interval;
		}

		return $atWorkTimeSum;
	}

	protected function getCommentsCount(TaskCollection $tasks): int
	{
		if (!Loader::includeModule('forum'))
		{
			return 0;
		}

		if ($tasks->isEmpty())
		{
			return 0;
		}

		$taskIds = [];
		foreach ($tasks as $task)
		{
			$taskIds[] = (int)$task['ID'];
		}

		$xmlIds = array_map(static fn(int $id): string => 'TASK_' . $id, $taskIds);

		$commentsCount =
			MessageTable::query()
				->setSelect([Query::expr('COUNT')->countDistinct('ID')])
				->whereIn('XML_ID', $xmlIds)
				->whereNull('SERVICE_TYPE')
				->whereNull('PARAM1')
				->exec()
				->fetch()
		;

		return (int)($commentsCount['COUNT'] ?? 0);
	}

	protected function getPostponeCount(TaskCollection $tasks): int
	{
		$count = 0;

		foreach ($tasks as $task)
		{
			$taskId = (int)$task['ID'];

			$postponeCountQuery =
				LogTable::query()
					->setSelect([Query::expr('COUNT')->countDistinct('ID')])
					->where('TASK_ID', $taskId)
					->where('FIELD', 'DEADLINE')
			;

			$taskMovedToFlowDate = $this->getTaskMovedToFlowDate($taskId);

			if (null !== $taskMovedToFlowDate)
			{
				$postponeCountQuery->where('CREATED_DATE', '>', $taskMovedToFlowDate);
			}

			$postponeCount = $postponeCountQuery->exec()->fetch();
			$postponeCount = (int)($postponeCount['COUNT'] ?? 0);

			$count += $postponeCount;
		}

		return $count;
	}

	protected function getFirstTaskCreatedDate(TaskCollection $tasks): DateTime
	{
		$firstTaskCreatedDate = new DateTime();
		foreach ($tasks as $task)
		{
			$createdDate = $this->getTaskCreatedDate($task);
			if (!$createdDate instanceof DateTime)
			{
				continue;
			}

			if ($createdDate->getTimestamp() < $firstTaskCreatedDate->getTimestamp())
			{
				$firstTaskCreatedDate = $createdDate;
			}
		}

		return $firstTaskCreatedDate;
	}

	protected function getTaskCreatedDate(array $task): ?DateTime
	{
		$taskId = (int)$task['ID'];

		$taskMovedToFlowDate = $this->getTaskMovedToFlowDate($taskId);

		$createdDate = $taskMovedToFlowDate ?? $task['CREATED_DATE'] ?? null;
		if (!$createdDate instanceof DateTime)
		{
			return null;
		}

		return $createdDate;
	}

	protected function getTaskMovedToFlowDate(int $taskId): ?DateTime
	{
		$isTaskCreatedInFlow =
			LogTable::query()
				->setSelect(['FROM_VALUE', 'TO_VALUE', 'CREATED_DATE'])
				->where('TASK_ID', $taskId)
				->where('FIELD', 'FLOW_ID')
				->setOrder(['CREATED_DATE' => 'DESC'])
				->exec()
				->fetch()
		;

		return $isTaskCreatedInFlow['CREATED_DATE'] ?? null;
	}
}
