<?php

namespace Bitrix\Tasks\Integration\CRM\Timeline\Event;

use Bitrix\Tasks\Integration\CRM\Timeline\EventTrait;
use Bitrix\Tasks\Integration\Disk\Connector\Task;
use Bitrix\Tasks\Internals\TaskObject;

class OnTaskAdded implements TimeLineEvent
{
	use EventTrait;
	private ?TaskObject $task;
	private int $userId;
	private bool $restored;

	public function __construct(?TaskObject $task, int $userId, bool $restored = false)
	{
		$this->userId = $userId;
		$this->task = $task;
		$this->restored = $restored;
	}

	public function getPayload(): array
	{
		return [
			'TASK_FILE_IDS' => $this->getFiles($this->task->getId(), Task::class),
			'TASK_ID' => $this->task->getId(),
			'AUTHOR_ID' => $this->task->getCreatedBy() ?? $this->userId,
			'RESPONSIBLE_ID' => $this->task->getResponsibleId(),
			'REFRESH_TASK_ACTIVITY' => false,
			'TASK_RESTORED' => $this->restored,
		];
	}

	public function getEndpoint(): string
	{
		return 'onTaskAdded';
	}

	public function getPriority(): int
	{
		return 100;
	}
}