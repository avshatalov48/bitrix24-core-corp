<?php

namespace Bitrix\Tasks\Integration\CRM\Timeline\Event;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Integration\CRM\Timeline\EventTrait;
use Bitrix\Tasks\Internals\TaskObject;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\Type\DateTime;
use CTimeZone;

class OnTaskDeadLineChanged implements TimeLineEvent
{
	use EventTrait;
	private ?TaskObject $task;
	private int $userId;
	private ?TaskObject $previousTaskState;

	public function __construct(?TaskObject $task, ?TaskObject $previousTaskState, int $userId)
	{
		$this->userId = $userId;
		$this->task = $task;
		$this->previousTaskState = $previousTaskState;
	}

	public function getPayload(): array
	{
		return [
			'AUTHOR_ID' => $this->userId,
			'TASK_ID' => $this->task->getId(),
			'DEADLINE' => $this->task->getDeadline(),
			'TASK_PREV_DEADLINE' => $this->formatDateTime($this->previousTaskState->getDeadline()),
			'TASK_CURR_DEADLINE' => $this->formatDateTime($this->task->getDeadline()),
			'UPDATE_ACTIVITY_STATUS' => $this->updateActivityStatus(),
			'REFRESH_TASK_ACTIVITY' => $this->updateActivityStatus() === false && !$this->task->isExpired(),
		];
	}

	public function getEndpoint(): string
	{
		return 'onTaskDeadLineChanged';
	}

	private function formatDateTime(?string $dateTime): string
	{
		$timestamp = $this->getDateTimestamp($dateTime);
		if (!$timestamp)
		{
			return Loc::getMessage('TASKS_ON_DEADLINE_CHANGED_EVENT_NO_DEADLINE');
		}

		$format = UI::getHumanDateTimeFormat($timestamp);
		return UI::formatDateTime($timestamp, $format);
	}

	private function updateActivityStatus(): bool
	{
		return !($this->task->isExpired() && $this->previousTaskState->isExpired());
	}

	private function getDateTimestamp(?string $dateTime): ?int
	{
		if (!$dateTime)
		{
			return null;
		}

		$timestamp = MakeTimeStamp($dateTime);

		if ($timestamp === false)
		{
			$timestamp = strtotime($dateTime);
			if ($timestamp !== false)
			{
				$timestamp += CTimeZone::GetOffset() - DateTime::createFromTimestamp($timestamp)->getSecondGmt();
			}
		}

		return $timestamp;
	}
}