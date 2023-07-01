<?php

namespace Bitrix\Tasks\Integration\CRM\Timeline\Event;

use Bitrix\Tasks\Integration\CRM\Timeline\EventTrait;
use Bitrix\Tasks\Internals\TaskObject;

class OnTaskDisapproved implements TimeLineEvent
{
	use EventTrait;
	private ?TaskObject $task;
	private int $previousStatus;
	private int $currentStatus;
	private int $userId;

	public function __construct(?TaskObject $task, int $currentStatus, int $previousStatus, int $userId)
	{
		$this->task = $task;
		$this->previousStatus = $previousStatus;
		$this->currentStatus = $currentStatus;
		$this->userId = $userId;
	}


	public function getPayload(): array
	{
		return [
			'TASK_ID' => $this->task->getId(),
			'AUTHOR_ID' => $this->userId,
			'TASK_PREVIOUS_STATUS' => $this->previousStatus,
			'TASK_CURRENT_STATUS' => $this->currentStatus,
			'REFRESH_TASK_ACTIVITY' => true,
		];
	}

	public function getEndpoint(): string
	{
		return 'onTaskDisapproved';
	}
}