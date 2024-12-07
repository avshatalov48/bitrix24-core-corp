<?php

namespace Bitrix\Tasks\Flow\Notification\Command;

class SendPingCommand
{
	private int $taskId;
	private int $flowId;
	private int $offset;

	public function __construct(int $taskId, int $flowId, int $offset)
	{
		$this->taskId = $taskId;
		$this->flowId = $flowId;
		$this->offset = $offset;
	}

	public function getTaskId(): int
	{
		return $this->taskId;
	}

	public function getFlowId(): int
	{
		return $this->flowId;
	}

	public function getOffset(): int
	{
		return $this->offset;
	}
}