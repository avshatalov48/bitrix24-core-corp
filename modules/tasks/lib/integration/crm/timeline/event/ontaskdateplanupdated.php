<?php

namespace Bitrix\Tasks\Integration\CRM\Timeline\Event;

use Bitrix\Tasks\Integration\CRM\Timeline\EventTrait;
use Bitrix\Tasks\Internals\TaskObject;

class OnTaskDatePlanUpdated implements TimeLineEvent
{
	use EventTrait;
	private ?TaskObject $task;
	private int $userId;

	public function __construct(?TaskObject $task, int $userId)
	{
		$this->task = $task;
		$this->userId = $userId;
	}

	public function getPayload(): array
	{
		return [
			'AUTHOR_ID' => $this->userId,
			'TASK_ID' => $this->task->getId(),
			'IGNORE_IN_LOGS' => true,
			'REFRESH_TASK_ACTIVITY' => false,
		];
	}

	public function getEndpoint(): string
	{
		return 'OnTaskDatePlanUpdated';
	}

	public function getPriority(): int
	{
		return 0;
	}
}