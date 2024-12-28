<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Flow\Distribution\FlowDistributionType;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\Integration\HumanResources\AccessCodeConverter;
use Bitrix\Tasks\Flow\Provider\Exception\FlowNotFoundException;
use Bitrix\Tasks\Flow\Provider\FlowProvider;
use Bitrix\Tasks\Internals\Task\LogTable;
use Bitrix\Tasks\Provider\TaskList;
use Bitrix\Tasks\Provider\TaskQuery;
use InvalidArgumentException;
use Psr\Container\NotFoundExceptionInterface;

final class Registry
{
	private static array $instance = [];

	private int $flowId;
	private array $taskIds;
	private array $distributionTimes = [];
	private array $employees = [];

	private Flow $flow;
	private TaskCollection $tasks;

	public static function getInstance(int $flowId, array $taskIds = []): self
	{
		$index = $flowId . '.' . implode('.', $taskIds);
		if (!isset(self::$instance[$index]))
		{
			self::$instance[$index] = new self($flowId, $taskIds);
		}

		return self::$instance[$index];
	}

	private function __construct(int $flowId, array $taskIds)
	{
		$this->flowId = $flowId;
		$this->taskIds = $taskIds;

		$this->init();
	}

	public function getFlow(): Flow
	{
		return $this->flow;
	}

	public function getTasks(): TaskCollection
	{
		return $this->tasks;
	}

	public function getDistributionTimes(): array
	{
		return $this->distributionTimes;
	}

	public function getEmployees(): array
	{
		return $this->employees;
	}

	private function init(): void
	{
		$this->fetchFlow();
		$this->fetchTasks();
		$this->fetchEmployees();
		$this->fetchDistributionTimes();
	}

	private function fetchFlow(): void
	{
		// todo try catch
		$this->flow = (new FlowProvider())->getFlow($this->flowId, ['*', 'OPTIONS']);
	}

	private function fetchTasks(): void
	{
		if ([] === $this->taskIds)
		{
			$this->tasks = new TaskCollection();

			return;
		}
		$select = [
			'ID',
			'TITLE',
			'RESPONSIBLE_ID',
			'CREATED_BY',
			'STATUS',
			'REAL_STATUS',
			'CLOSED_DATE',
			'DEADLINE',
			'DATE_START',
			'CREATED_DATE',
		];

		$query = (new TaskQuery())
			->skipAccessCheck()
			->setSelect($select)
			->setWhere(['@ID' => $this->taskIds]);

		// todo try catch
		$tasks = (new TaskList())->getList($query);
		$this->tasks = new TaskCollection($tasks);
	}

	private function fetchEmployees(): void
	{
		$collections = [
			$this->tasks->getCompleted()->getNotExpired(),
			$this->tasks->getExpired(),
		];

		foreach ($collections as $tasks)
		{
			foreach ($tasks as $task)
			{
				$taskId = (int)$task['ID'];

				$this->employees[] = (int)$this->tasks[$taskId]['RESPONSIBLE_ID'];
			}
		}

		$this->employees = array_unique($this->employees);
	}

	private function fetchDistributionTimes(): void
	{
		foreach ($this->tasks as $task)
		{
			$taskId = (int)$task['ID'];
			$createdDate = $this->getTaskCreatedDate($task);
			if (!$createdDate instanceof DateTime)
			{
				continue;
			}

			$distributionDate = $this->getDistributionTime($task);

			$this->distributionTimes[$taskId] = $distributionDate ?? $createdDate;
		}

	}

	private function getTaskMovedToFlowDate(int $taskId): ?DateTime
	{
		$isTaskCreatedInFlow = LogTable::query()
		                               ->setSelect(['FROM_VALUE', 'TO_VALUE', 'CREATED_DATE'])
		                               ->where('TASK_ID', $taskId)
		                               ->where('FIELD', 'FLOW_ID')
		                               ->setOrder(['CREATED_DATE' => 'DESC'])
		                               ->exec()
		                               ->fetch();

		return $isTaskCreatedInFlow['CREATED_DATE'] ?? null;
	}

	private function getDistributionTime(array $task): ?DateTime
	{
		$taskId = (int)$task['ID'];

		$taskMovedToFlowDate = $this->getTaskMovedToFlowDate($taskId);

		$createdDate = $taskMovedToFlowDate ?? $task['CREATED_DATE'] ?? null;
		if (!$createdDate instanceof DateTime)
		{
			return null;
		}

		if (!$this->flow->isManually())
		{
			return null;
		}

		$delegationQuery = LogTable::query()
		                           ->setSelect(['CREATED_DATE'])
		                           ->where('FROM_VALUE', $this->getManualDistributorId())
		                           ->where('TASK_ID', $taskId)
		                           ->where('FIELD', 'RESPONSIBLE_ID')
		                           ->setOrder(['CREATED_DATE' => 'ASC']);

		if (null !== $taskMovedToFlowDate)
		{
			$delegationQuery->where('CREATED_DATE', '>', $taskMovedToFlowDate);
		}

		$delegationLog = $delegationQuery->exec()->fetch();

		$distributionTime = $delegationLog['CREATED_DATE'] ?? null;
		if (!$distributionTime instanceof DateTime)
		{
			return null;
		}

		return $distributionTime;
	}

	/**
	 * @throws LoaderException
	 * @throws NotFoundExceptionInterface
	 * @throws ObjectNotFoundException
	 * @throws FlowNotFoundException
	 */
	private function getManualDistributorId(): int
	{
		if ($this->flow->getDistributionType() !== FlowDistributionType::MANUALLY)
		{
			throw new InvalidArgumentException('Manual distributor id is only available for manual flow.');
		}

		$memberFacade = ServiceLocator::getInstance()->get('tasks.flow.member.facade');
		$manualDistributorAccessCode = $memberFacade->getResponsibleAccessCodes($this->flow->getId())[0];

		return (new AccessCodeConverter($manualDistributorAccessCode))
			->getAccessCodeIdList()[0]
		;
	}

	private function getTaskCreatedDate(array $task): ?DateTime
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
}
