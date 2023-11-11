<?php

namespace Bitrix\Tasks\Replicator;

use Bitrix\Tasks\Replicator\Template\Repository;

interface Checker
{
	public function __construct(Repository $repository);
	public function stopReplicationByInvalidData(): bool;
	public function stopCurrentReplicationByPostpone(): bool;
}