<?php

namespace Bitrix\Tasks\Flow\Notification\Command;

class UpdatePingCommand
{
	private int $taskId;

	public function __construct(int $taskId)
	{
		$this->taskId = $taskId;
	}

	public function getTaskId(): int
	{
		return $this->taskId;
	}
}