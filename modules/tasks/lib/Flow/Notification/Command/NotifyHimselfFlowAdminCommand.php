<?php

namespace Bitrix\Tasks\Flow\Notification\Command;

class NotifyHimselfFlowAdminCommand
{
	private int $taskId;
	private int $flowId;

	public function __construct(int $taskId, int $flowId)
	{
		$this->taskId = $taskId;
		$this->flowId = $flowId;
	}

	public function getTaskId(): int
	{
		return $this->taskId;
	}

	public function getFlowId(): int
	{
		return $this->flowId;
	}
}