<?php

namespace Bitrix\Tasks\Replication\Task\Regularity;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Tasks\Control\Task;
use Bitrix\Tasks\Replication\RepeaterInterface;
use Bitrix\Tasks\Replication\Task\Regularity\Exception\RegularityException;
use Bitrix\Tasks\Replication\Task\Regularity\Exception\RegularParameterException;
use Bitrix\Tasks\Replication\Task\Regularity\Time\Service\RegularityService;
use Bitrix\Tasks\Replication\RepositoryInterface;

class RegularTaskRepeater implements RepeaterInterface
{
	private RepositoryInterface $repository;
	private Result $currentResult;
	private int $copiedTaskId;
	private $additionalData = null;

	public function __construct(RepositoryInterface $repository)
	{
		$this->repository = $repository;
	}

	public function repeatTask(): Result
	{
		$this->copiedTaskId = $this->getAdditionalData()['copiedTaskId'];
		$this->currentResult = new Result();
		try
		{
			$this
				->markCopiedTaskAsRegular()
				->markCurrentTaskAsUnregular();
		}
		catch (RegularParameterException $exception)
		{
			$this->currentResult->addError(Error::createFromThrowable($exception));
			return $this->currentResult;
		}

		return $this->currentResult;
	}

	public function isDebug(): bool
	{
		return false;
	}

	/**
	 * @throws RegularityException
	 */
	private function markCopiedTaskAsRegular(): static
	{
		$originalTask = $this->repository->getEntity();
		$repositoryClass = $this->repository::class;
		$copiedTaskRepository = new $repositoryClass($this->copiedTaskId);

		$regularityService = new RegularityService($copiedTaskRepository);

		$regularityService->setRegularity(
			$originalTask->getRegularFields()->getRegularParameters(),
			$originalTask->getRegularFields()->getStartTime()
		);

		return $this;
	}

	/**
	 * @throws RegularityException
	 */
	private function markCurrentTaskAsUnregular(): static
	{
		$task = $this->repository->getEntity();
		$handler = new Task($task->getCreatedByMemberId());
		try
		{
			$handler->update($task->getId(), ['IS_REGULAR' => 'N']);
			$this->repository->drop();
		}
		catch (\Exception $exception)
		{
			throw new RegularParameterException($exception->getMessage());
		}

		$regularityService = new RegularityService($this->repository);
		$regularityService->unsetRegularity();

		return $this;
	}

	public function getAdditionalData()
	{
		return $this->additionalData;
	}

	public function setAdditionalData($data): void
	{
		$this->additionalData = $data;
	}
}