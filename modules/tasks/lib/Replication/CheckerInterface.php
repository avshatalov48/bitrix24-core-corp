<?php

namespace Bitrix\Tasks\Replication;

use Bitrix\Tasks\Replication\RepositoryInterface;

interface CheckerInterface
{
	public function __construct(RepositoryInterface $repository);
	public function stopReplicationByInvalidData(): bool;
	public function stopCurrentReplicationByPostpone(): bool;
	public function setUserId(int $userId): static;
}