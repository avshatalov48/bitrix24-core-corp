<?php

namespace Bitrix\Tasks\Replication\Task\Regularity;

use Bitrix\Tasks\Replication\CheckerInterface;
use Bitrix\Tasks\Replication\RepositoryInterface;

class RegularTaskChecker implements CheckerInterface
{
	private RepositoryInterface $repository;
	private int $userId = 0;

	public function __construct(RepositoryInterface $repository)
	{
		$this->repository = $repository;
	}

	public function stopReplicationByInvalidData(): bool
	{
		return !$this->repository->getEntity()->isRegular();
	}

	public function stopCurrentReplicationByPostpone(): bool
	{
		return !$this->repository->getEntity()->isCompleted();
	}

	public function setUserId(int $userId): static
	{
		$this->userId = $userId;
		return $this;
	}
}