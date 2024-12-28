<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Stepper\Filler\Type\Multiple;

use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Flow\Integration\AI\Result\CollectorResult;
use Bitrix\Tasks\Flow\Integration\AI\Result\Node\EntityType;
use Bitrix\Tasks\Flow\Integration\AI\Result\Node\Type\AverageNode;
use Bitrix\Tasks\Flow\Integration\AI\Result\Node\Type\MergeNode;
use Bitrix\Tasks\Flow\Integration\AI\Result\Node\Type\PercentageNode;
use Bitrix\Tasks\Flow\Integration\AI\Result\Node\Type\SumNode;
use Bitrix\Tasks\Flow\Integration\AI\Stepper\Filler\AbstractFiller;
use Bitrix\Tasks\Flow\Integration\AI\Stepper\Filler\TaskCollectionTrait;
use Bitrix\Tasks\Flow\Integration\AI\TaskCollection;

class TaskFiller extends AbstractFiller
{
	use TaskCollectionTrait;

	protected TaskCollection $tasks;
	protected array $distributionTimes;

	public function fill(CollectorResult $result): void
	{
		$this->result = $result;

		$this->fillEmployees();
		$this->fillCreatedDates();
		$this->fillTasksCount();
		$this->fillAverageLifeTime();
		$this->fillAverageCommentsCount();
		$this->fillAverageDistributionTime();
		$this->fillAverageAtWorkTime();

		$this->fillAverageDeadlinePostponementCount();
		$this->fillAverageDeadlinePeriod();
		$this->fillAverageDailyTasksCount();
		$this->fillTasksToCompletedPercentage();
	}

	private function fillEmployees(): void
	{
		$node = (new MergeNode(
			EntityType::TASK,
			'employee_task_distribution',
			array_map(fn (int $id): string => $this->formatUserIdForNode($id), $this->employees),
		))->setUnique();

		$this->result->addNode($node);
	}


	private function fillCreatedDates(): void
	{
		$createdDates = [];
		foreach ($this->tasks as $task)
		{
			$createdDate = $this->getTaskCreatedDate($task);
			if (!$createdDate instanceof DateTime)
			{
				continue;
			}
			$value = $createdDates[$createdDate->format('Y.m.d')] ?? null;

			if (null !== $value)
			{
				++$createdDates[$createdDate->format('Y.m.d')];
			}
			else
			{
				$createdDates[$createdDate->format('Y.m.d')] = 1;
			}
		}

		foreach ($createdDates as $date => $count)
		{
			$node = new SumNode(
				EntityType::TASK,
				'count',
				$count,
				$date,
				'tasks_by_date',
			);

			$this->result->addNode($node);
		}
	}

	private function fillTasksCount(): void
	{
		$collections = [
			$this->tasks->getCompleted()->getNotExpired(),
			$this->tasks->getExpired(),
		];

		foreach ($collections as $tasks)
		{
			$nodeName = $tasks->getType() . '_count';

			if (!str_contains($nodeName, 'completed'))
			{
				$nodeName = 'total_' . $nodeName;
			}

			$node = new SumNode(
				EntityType::TASK,
				$nodeName,
				$tasks->getCount(),
			);

			$this->result->addNode($node);
		}
	}

	private function fillAverageCommentsCount(): void
	{
		$collections = [
			$this->tasks->getCompleted()->getNotExpired(),
			$this->tasks->getCompleted()->getExpired(),
		];

		foreach ($collections as $tasks)
		{
			$node = new AverageNode(
				EntityType::TASK,
				$tasks->getType() . '_avg_comments_count',
				$this->getCommentsCount($tasks),
				$tasks->getCount(),
			);

			$this->result->addNode($node);
		}
	}

	private function fillAverageDeadlinePostponementCount(): void
	{
		$collections = [
			$this->tasks->getCompleted()->getExpired(),
		];

		foreach ($collections as $tasks)
		{
			$node = new AverageNode(
				EntityType::TASK,
				$tasks->getType() . '_deadline_postponement_count',
				$this->getPostponeCount($tasks),
				$tasks->getCount(),
			);

			$this->result->addNode($node);
		}
	}

	private function fillAverageDeadlinePeriod(): void
	{
		$collections = [
			$this->tasks->getCompleted()->getExpired(),
		];

		foreach ($collections as $tasks)
		{
			$node = new AverageNode(
				EntityType::TASK,
				$tasks->getType() . '_deadline_period',
				$this->getDeadlinePeriodSum($tasks),
				$tasks->getCount(),
			);

			$this->result->addNode($node);
		}
	}

	private function fillAverageDailyTasksCount(): void
	{
		$firstTaskCreatedDate = $this->getFirstTaskCreatedDate($this->tasks);

		$today = new Date();
		$daysPassedFromFirstTask = $today->getDiff($firstTaskCreatedDate)->days ?: 1;

		$node = new AverageNode(
			EntityType::TASK,
			'avg_daily_tasks_count',
			$this->tasks->getCount(),
			$daysPassedFromFirstTask,
		);

		$this->result->addNode($node);
	}

	private function fillAverageDistributionTime(): void
	{
		if (!$this->flow->isManually())
		{
			return;
		}

		$collection = [
			$this->tasks->getCompleted()->getNotExpired(),
			$this->tasks->getCompleted()->getExpired(),
		];

		foreach ($collection as $tasks)
		{
			$node = new AverageNode(
				EntityType::TASK,
				$tasks->getType() . '_avg_manual_distribution_time',
				$this->getDistributionTimeSum($tasks),
				$tasks->getCount(),
			);

			$this->result->addNode($node);
		}
	}

	private function fillAverageAtWorkTime(): void
	{
		if (!$this->flow->isManually())
		{
			return;
		}

		$collections = [
			$this->tasks->getCompleted()->getNotExpired(),
			$this->tasks->getCompleted()->getExpired(),
		];

		foreach ($collections as $tasks)
		{
			$atWorkNode = new AverageNode(
				EntityType::TASK,
				$tasks->getType() . '_avg_in_process_time',
				$this->getAtWorkTimeSum($tasks),
				$tasks->getCount(),
			);

			$this->result->addNode($atWorkNode);
		}
	}

	private function fillAverageLifeTime(): void
	{
		$collections = [
			$this->tasks->getCompleted()->getNotExpired(),
			$this->tasks->getCompleted()->getExpired(),
		];

		foreach ($collections as $tasks)
		{
			$node = new AverageNode(
				EntityType::TASK,
				$tasks->getType() . '_avg_life_time',
				$this->getLifeTimeSum($tasks),
				$tasks->getCount(),
			);

			$this->result->addNode($node);
		}
	}

	private function fillTasksToCompletedPercentage(): void
	{
		$collections = [
			$this->tasks->getCompleted()->getExpired(),
		];

		foreach ($collections as $tasks)
		{
			$node = new PercentageNode(
				EntityType::TASK,
				$tasks->getType() . '_percentage',
				$tasks->getCount(),
				$this->tasks->getCompleted()->getCount(),
			);

			$this->result->addNode($node);
		}
	}

	protected function init(): void
	{
		$this->flow = $this->registry->getFlow();
		$this->tasks = $this->registry->getTasks();
		$this->employees = $this->registry->getEmployees();
		$this->distributionTimes = $this->registry->getDistributionTimes();
	}
}
