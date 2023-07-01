<?php

namespace Bitrix\Tasks\Integration\CRM\Timeline\Event;

use Bitrix\Tasks\Integration\CRM\Timeline\EventTrait;
use Bitrix\Tasks\Internals\TaskObject;

class OnTaskCommentDeleted implements TimeLineEvent
{
	use EventTrait;

	private ?TaskObject $task;
	private int $userId;
	private array $fileIds;

	public function __construct(
		?TaskObject $task,
		int $userId,
		array $fileIds = []
	)
	{
		$this->task = $task;
		$this->userId = $userId;
		$this->fileIds = $fileIds;
	}

	public function getPayload(): array
	{
		return [
			'AUTHOR_ID' => $this->userId,
			'TASK_FILE_IDS' => $this->fileIds,
			'TASK_ID' => $this->task->getId(),
			'REFRESH_TASK_ACTIVITY' => true,
		];
	}

	public function getEndpoint(): string
	{
		return 'onTaskCommentDeleted';
	}
}