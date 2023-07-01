<?php

namespace Bitrix\Tasks\Integration\CRM\Timeline\Event;

use Bitrix\Tasks\Integration\CRM\Timeline\EventTrait;
use Bitrix\Tasks\Internals\TaskObject;
use CTasks;

class OnTaskStatusChanged implements TimeLineEvent
{
	use EventTrait;
	private ?TaskObject $task;
	private int $currentStatus;
	private int $previousStatus;
	private int $userId;

	public function __construct(?TaskObject $task, int $currentStatus, int $previousStatus, int $userId)
	{
		$this->task = $task;
		$this->currentStatus = $currentStatus;
		$this->previousStatus = $previousStatus;
		$this->userId = $userId;
	}

	public function getPayload(): array
	{
		return [
			'TASK_ID' => $this->task->getId(),
			'AUTHOR_ID' => $this->userId,
			'TASK_PREVIOUS_STATUS' => $this->previousStatus,
			'TASK_CURRENT_STATUS' => $this->currentStatus,
			'IGNORE_IN_LOGS' => $this->ignoreInLogs(),
			'SHOW_RETURNED_BACK_TO_WORK_TITLE' => $this->showReturnedBackToWorkTitle(),
			'REFRESH_TASK_ACTIVITY' => true,
		];
	}

	public function getEndpoint(): string
	{
		return 'onTaskStatusChanged';
	}

	private function showReturnedBackToWorkTitle(): bool
	{
		return $this->previousStatus === CTasks::STATE_COMPLETED
			&& ($this->currentStatus === CTasks::STATE_PENDING || $this->currentStatus === CTasks::STATE_IN_PROGRESS);
	}

	private function ignoreInLogs(): bool
	{
		if ($this->showReturnedBackToWorkTitle())
		{
			return false;
		}

		return $this->currentStatus === CTasks::STATE_COMPLETED
			|| in_array($this->previousStatus, [CTasks::STATE_COMPLETED, CTasks::STATE_SUPPOSEDLY_COMPLETED]);
	}
}