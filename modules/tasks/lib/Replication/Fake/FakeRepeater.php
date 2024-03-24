<?php

namespace Bitrix\Tasks\Replication\Fake;

use Bitrix\Main\Result;
use Bitrix\Tasks\Replication\RepeaterInterface;
use Bitrix\Tasks\Replication\RepositoryInterface;

class FakeRepeater implements RepeaterInterface
{
	public function __construct(RepositoryInterface $repository)
	{
	}

	public function repeatTask(): Result
	{
		return new Result();
	}

	public function isDebug(): bool
	{
		return false;
	}

	public function setAdditionalData($data): void
	{
	}

	public function getAdditionalData(): array
	{
		return [];
	}
}