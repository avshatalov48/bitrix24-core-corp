<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Stepper\Filler\Type\Multiple;

use Bitrix\Tasks\Flow\Efficiency\ResponsibleEfficiency;
use Bitrix\Tasks\Flow\Efficiency\LastMonth;
use Bitrix\Tasks\Flow\Integration\AI\Result\CollectorResult;
use Bitrix\Tasks\Flow\Integration\AI\Result\Node\EntityType;
use Bitrix\Tasks\Flow\Integration\AI\Result\Node\Type\AverageNode;
use Bitrix\Tasks\Flow\Integration\AI\Result\Node\Type\NestedSumNode;
use Bitrix\Tasks\Flow\Integration\AI\Result\Node\Type\PercentageNode;
use Bitrix\Tasks\Flow\Integration\AI\Result\Node\Type\SumNode;
use Bitrix\Tasks\Flow\Integration\AI\Result\Node\Type\ValueNode;
use Bitrix\Tasks\Flow\Integration\AI\Stepper\Filler\AbstractFiller;
use Bitrix\Tasks\Flow\Integration\AI\Stepper\Filler\TaskCollectionTrait;
use Bitrix\Tasks\Flow\Integration\AI\TaskCollection;

class UserFiller extends AbstractFiller
{
	use TaskCollectionTrait;

	protected TaskCollection $tasks;
	protected array $distributionTimes;

	public function fill(CollectorResult $result): void
	{
		$this->result = $result;

		$this->fillEfficiency();

		$this->fillTasksCount();
		$this->fillTasksCreators();
		$this->fillTasksAverageLifeTime();
		$this->fillTasksAverageCommentsCount();
		$this->fillTasksAverageDistributionTime();
		$this->fillTasksAverageAtWorkTime();
		$this->fillTasksAverageDeadlinePostponementCount();
		$this->fillTasksAverageDeadlinePeriod();
		$this->fillTasksToCompletedPercentage();
	}

	private function fillEfficiency(): void
	{
		foreach ($this->employees as $employeeId)
		{
			$node = new ValueNode(
				EntityType::EMPLOYEE,
				'employee_efficiency_percentage',
				(new ResponsibleEfficiency(new LastMonth(), $this->flow->getId()))->get($employeeId),
				$this->formatUserIdForNode($employeeId),
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
			foreach ($this->employees as $employeeId)
			{

				/** @var TaskCollection $tasks */
				$tasksByEmployee = $tasks->getByEmployeeId($employeeId);

				$nodeName = $tasksByEmployee->getType() . '_count';

				if (!str_contains($nodeName, 'completed'))
				{
					$nodeName = 'total_' . $nodeName;
				}

				$node = new SumNode(
					EntityType::EMPLOYEE,
					$nodeName,
					$tasksByEmployee->getCount(),
					$this->formatUserIdForNode($employeeId),
				);

				$this->result->addNode($node);
			}
		}
	}

	private function fillTasksCreators(): void
	{
		$collections = [
			$this->tasks->getCompleted()->getNotExpired(),
			$this->tasks->getCompleted()->getExpired(),
		];

		foreach ($collections as $tasks)
		{
			foreach ($this->employees as $employeeId)
			{
				/** @var TaskCollection $tasks */
				$tasksByEmployee = $tasks->getByEmployeeId($employeeId);

				$tasksByCreator = $tasksByEmployee->groupByCreator();
				foreach ($tasksByCreator as $creatorId => $taskCollection)
				{
					$node = (new NestedSumNode(
						EntityType::EMPLOYEE,
						$taskCollection->getType() . '_by_creator',
						[
							[
								'value' => $taskCollection->getCount(),
								'identifier' => $this->formatUserIdForNode($creatorId),
							]
						],
						$this->formatUserIdForNode($employeeId),
					));

					$this->result->addNode($node);
				}
			}
		}

	}

	private function fillTasksAverageDeadlinePostponementCount(): void
	{
		$collections = [
			$this->tasks->getCompleted()->getExpired(),
		];

		foreach ($collections as $tasks)
		{
			foreach ($this->employees as $employeeId)
			{
				/** @var TaskCollection $tasks */
				$tasksByEmployee = $tasks->getByEmployeeId($employeeId);

				$node = new AverageNode(
					EntityType::EMPLOYEE,
					$tasksByEmployee->getType() . '_deadline_postponement_count',
					$this->getPostponeCount($tasksByEmployee),
					$tasksByEmployee->getCount(),
					$this->formatUserIdForNode($employeeId),
				);

				$this->result->addNode($node);
			}
		}
	}

	private function fillTasksAverageDeadlinePeriod(): void
	{
		$collections = [
			$this->tasks->getCompleted()->getExpired(),
		];

		foreach ($collections as $tasks)
		{
			foreach ($this->employees as $employeeId)
			{
				/** @var TaskCollection $tasks */
				$tasksByEmployee = $tasks->getByEmployeeId($employeeId);

				$node = new AverageNode(
					EntityType::EMPLOYEE,
					$tasksByEmployee->getType() . '_deadline_period',
					$this->getDeadlinePeriodSum($tasksByEmployee),
					$tasksByEmployee->getCount(),
					$this->formatUserIdForNode($employeeId),
				);

				$this->result->addNode($node);
			}
		}
	}

	private function fillTasksAverageDistributionTime(): void
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
			foreach ($this->employees as $employeeId)
			{
				/** @var TaskCollection$tasks */
				$tasksByEmployee = $tasks->getByEmployeeId($employeeId);

				$node = new AverageNode(
					EntityType::EMPLOYEE,
					$tasksByEmployee->getType() . '_avg_manual_distribution_time',
					$this->getDistributionTimeSum($tasksByEmployee),
					$tasksByEmployee->getCount(),
					$this->formatUserIdForNode($employeeId),
				);

				$this->result->addNode($node);
			}
		}
	}

	private function fillTasksAverageAtWorkTime(): void
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
			foreach ($this->employees as $employeeId)
			{
				/** @var TaskCollection $tasks */
				$tasksByEmployee = $tasks->getByEmployeeId($employeeId);

				$node = new AverageNode(
					EntityType::EMPLOYEE,
					$tasksByEmployee->getType() . '_avg_in_process_time',
					$this->getAtWorkTimeSum($tasksByEmployee),
					$tasksByEmployee->getCount(),
					$this->formatUserIdForNode($employeeId),
				);

				$this->result->addNode($node);
			}
		}
	}

	private function fillTasksAverageCommentsCount(): void
	{
		$collections = [
			$this->tasks->getCompleted()->getNotExpired(),
			$this->tasks->getCompleted()->getExpired(),
		];

		foreach ($collections as $tasks)
		{
			foreach ($this->employees as $employeeId)
			{
				/** @var TaskCollection $tasks */
				$tasksByEmployee = $tasks->getByEmployeeId($employeeId);
				$commentsCount = $this->getCommentsCount($tasksByEmployee);

				$node = new AverageNode(
					EntityType::EMPLOYEE,
					$tasksByEmployee->getType() . '_avg_comments_count',
					$commentsCount,
					$tasksByEmployee->getCount(),
					$this->formatUserIdForNode($employeeId),
				);

				$this->result->addNode($node);
			}
		}
	}

	private function fillTasksAverageLifeTime(): void
	{
		$collections = [
			$this->tasks->getCompleted()->getNotExpired(),
			$this->tasks->getCompleted()->getExpired(),
		];

		foreach ($collections as $tasks)
		{
			foreach ($this->employees as $employeeId)
			{
				/** @var TaskCollection$tasks */
				$tasksByEmployee = $tasks->getByEmployeeId($employeeId);

				$node = new AverageNode(
					EntityType::EMPLOYEE,
					$tasksByEmployee->getType() . '_avg_life_time',
					$this->getLifeTimeSum($tasksByEmployee),
					$tasksByEmployee->getCount(),
					$this->formatUserIdForNode($employeeId),
				);

				$this->result->addNode($node);
			}
		}
	}

	private function fillTasksToCompletedPercentage(): void
	{
		$collections = [
			$this->tasks->getCompleted()->getExpired(),
		];

		foreach ($collections as $tasks)
		{
			foreach ($this->employees as $employeeId)
			{
				/** @var TaskCollection $tasks */
				$tasksByEmployee = $tasks->getByEmployeeId($employeeId);
				$allTasksByEmployee = $this->tasks->getByEmployeeId($employeeId);

				$node = new PercentageNode(
					EntityType::EMPLOYEE,
					$tasksByEmployee->getType() . '_percentage',
					$tasksByEmployee->getCount(),
					$allTasksByEmployee->getCompleted()->getCount(),
					$this->formatUserIdForNode($employeeId),
				);

				$this->result->addNode($node);
			}
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
