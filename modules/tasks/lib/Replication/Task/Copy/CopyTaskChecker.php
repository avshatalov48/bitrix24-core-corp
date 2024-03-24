<?php

namespace Bitrix\Tasks\Replication\Task\Copy;

use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\Model\TaskModel;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Replication\CheckerInterface;
use Bitrix\Tasks\Replication\RepositoryInterface;

class CopyTaskChecker implements CheckerInterface
{
	private RepositoryInterface $repository;
	private int $userId = 0;

	public function __construct(RepositoryInterface $repository)
	{
		$this->repository = $repository;
	}

	public function stopReplicationByInvalidData(): bool
	{
		return !TaskAccessController::can(
			$this->userId,
			ActionDictionary::ACTION_TASK_SAVE,
			$this->repository->getEntity()->getId(),
			TaskModel::createFromId($this->repository->getEntity()->getId())
		);
	}

	public function stopCurrentReplicationByPostpone(): bool
	{
		return false;
	}

	public function setUserId(int $userId): static
	{
		$this->userId = $userId;
		return $this;
	}
}