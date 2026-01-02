<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Provider;

use Bitrix\Tasks\V2\Internal\Entity\Result;
use Bitrix\Tasks\V2\Internal\Entity\ResultCollection;
use Bitrix\Tasks\V2\Internal\Repository\TaskResultRepositoryInterface;

class TaskResultProvider
{
	private TaskResultRepositoryInterface $repository;

	public function __construct(TaskResultRepositoryInterface $repository)
	{
		$this->repository = $repository;
	}

	public function getResultById(int $resultId): ?Result
	{
		return $this->repository->getById($resultId);
	}

	public function getTaskResults(int $taskId): ResultCollection
	{
		return $this->repository->getByTask($taskId);
	}
}
