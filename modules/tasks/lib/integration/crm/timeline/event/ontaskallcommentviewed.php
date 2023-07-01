<?php

namespace Bitrix\Tasks\Integration\CRM\Timeline\Event;

use Bitrix\Tasks\Integration\CRM\Timeline\EventTrait;
use Bitrix\Tasks\Internals\TaskObject;

class OnTaskAllCommentViewed implements TimeLineEvent
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
			'TASK_ID' => is_null($this->task) ? 0 : $this->task->getId(),
			'AUTHOR_ID' => $this->userId,
			'REFRESH_TASK_ACTIVITY' => true,
		];
	}

	public function getEndpoint(): string
	{
		return 'onTaskAllCommentViewed';
	}
}