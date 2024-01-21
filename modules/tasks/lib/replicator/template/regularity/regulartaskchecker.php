<?php

namespace Bitrix\Tasks\Replicator\Template\Regularity;

use Bitrix\Tasks\Replicator\CheckerInterface;
use Bitrix\Tasks\Replicator\Template\RepositoryInterface;

class RegularTaskChecker implements CheckerInterface
{
	private RepositoryInterface $repository;
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
}