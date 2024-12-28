<?php

namespace Bitrix\Crm\Service\Timeline\Item\Bizproc;

class TaskBlockData
{
	public readonly int $taskId;
	public readonly string $taskName;
	public readonly int $userId;
	public readonly int $rowLimit;

	public function __construct(int $taskId, string $taskName, int $userId = 0, int $rowLimit = 0)
	{
		$this->taskId = $taskId;
		$this->taskName = $taskName;
		$this->userId = $userId;
		$this->rowLimit = $rowLimit;
	}
}