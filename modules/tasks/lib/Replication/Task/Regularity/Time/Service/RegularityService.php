<?php

namespace Bitrix\Tasks\Replication\Task\Regularity\Time\Service;

use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Internals\Task\RegularParametersObject;
use Bitrix\Tasks\Internals\Task\RegularParametersTable;
use Bitrix\Tasks\Replication\Template\Option\Options;
use Bitrix\Tasks\Replication\Task\Regularity\Exception\RegularityException;
use Bitrix\Tasks\Replication\Task\Regularity\Exception\RegularParameterException;
use Bitrix\Tasks\Replication\Replicator\RegularTaskReplicator;
use Bitrix\Tasks\Replication\RepositoryInterface;

class RegularityService
{
	private ExecutionService $executionService;
	private array $regularParams;

	public function __construct(private RepositoryInterface $repository)
	{
		$this->executionService = new ExecutionService($this->repository);
	}

	/**
	 * @throws RegularityException
	 */
	public function setRegularity(array $fields = [], ?DateTime $lastStartTime = null): void
	{
		if (!RegularTaskReplicator::isEnabled())
		{
			return;
		}

		$task = $this->repository->getEntity();
		if (!$task->isRegular())
		{
			RegularParametersTable::deleteByTaskId($task->getId());
			return;
		}

		$this->regularParams = $fields;
		if (empty($this->regularParams))
		{
			return;
		}

		$parameter = $this->savePrimaryData();

		$startDateTime = $this->executionService->getNextRegularityDateTime($lastStartTime);
		$parameter
			->setStartTime($startDateTime)
			->setStartDay($startDateTime)
			->save();
	}

	public function unsetRegularity(): void
	{
		$task = $this->repository->getEntity();
		if (!$task->isRegular())
		{
			RegularParametersTable::deleteByTaskId($task->getId());
		}
	}

	/**
	 * @throws RegularParameterException
	 */
	private function savePrimaryData(): RegularParametersObject
	{
		$task = $this->repository->getEntity();
		$parameter = $task->getRegularFields() ?? new RegularParametersObject();

		$this->parseParams();
		$parameter
			->setTaskId($task->getId())
			->setRegularParameters($this->regularParams);

		$result = $parameter->save();
		if (!$result->isSuccess())
		{
			$message = $result->getErrors()[0]->getMessage();
			throw new RegularParameterException($message);
		}

		return $parameter;
	}

	private function parseParams(): static
	{
		$this->regularParams = Options::validate($this->regularParams);
		return $this;
	}
}