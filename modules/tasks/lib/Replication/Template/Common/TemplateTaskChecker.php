<?php

namespace Bitrix\Tasks\Replication\Template\Common;

use Bitrix\Tasks\Replication\CheckerInterface;
use Bitrix\Tasks\Replication\RepositoryInterface;

class TemplateTaskChecker implements CheckerInterface
{
	private RepositoryInterface $repository;
	private int $userId = 0;

	public function __construct(RepositoryInterface $repository)
	{
		$this->repository = $repository;
	}

	public function stopReplicationByInvalidData(): bool
	{
		return is_null($this->repository->getEntity());
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