<?php

namespace Bitrix\Tasks\Replicator\Template\common;

use Bitrix\Tasks\Replicator\CheckerInterface;
use Bitrix\Tasks\Replicator\Template\RepositoryInterface;

class TemplateTaskChecker implements CheckerInterface
{
	private RepositoryInterface $repository;
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
}