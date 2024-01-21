<?php

namespace Bitrix\Tasks\Replicator;

use Bitrix\Tasks\Replicator\Template\RepositoryInterface;

interface CheckerInterface
{
	public function __construct(RepositoryInterface $repository);
	public function stopReplicationByInvalidData(): bool;
	public function stopCurrentReplicationByPostpone(): bool;
}