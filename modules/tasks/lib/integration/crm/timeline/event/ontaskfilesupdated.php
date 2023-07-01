<?php

namespace Bitrix\Tasks\Integration\CRM\Timeline\Event;

use Bitrix\Tasks\Integration\CRM\Timeline\EventTrait;
use Bitrix\Tasks\Integration\Disk\Connector\Task;
use Bitrix\Tasks\Internals\TaskObject;

class OnTaskFilesUpdated implements TimeLineEvent
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
			'TASK_FILE_IDS' => $this->getFiles($this->task->getId(), Task::class),
			'REFRESH_TASK_ACTIVITY' => true,
		];
	}

	public function getEndpoint(): string
	{
		return 'onTaskFilesUpdated';
	}
}