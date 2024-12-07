<?php

namespace Bitrix\Tasks\Integration\IM\Notification;

use Bitrix\Tasks\Internals\Task\Status;

class MessageKey
{
	private const V2 = '_V2';

	private string $messageKey;

	public function __construct(string $messageKey)
	{
		$this->messageKey = $messageKey;
	}

	public function getWithVersion(): string
	{
		return match ($this->messageKey)
		{
			'TASKS_TASK_STATUS_MESSAGE_' . Status::PENDING => $this->messageKey . static::V2,
			default => $this->messageKey
		};
	}
}