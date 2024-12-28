<?php

namespace Bitrix\Tasks\Provider\Log;

use Bitrix\Tasks\Provider\Query\AbstractTaskQuery;
use Bitrix\Tasks\Provider\TaskQueryInterface;

class TaskLogQuery extends AbstractTaskQuery implements TaskQueryInterface
{
	protected bool $countTotal = false;

	public function getId(): string
	{
		return 0;
	}

	public function needAccessCheck(): bool
	{
		return false;
	}

	public function getCountTotal(): int
	{
		return $this->countTotal;
	}

	public function getUserId(): int
	{
		return 0;
	}
}
