<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Deadline\Entity;

use Bitrix\Main\Type\DateTime;

class DeadlineChangeLog
{
	public function __construct(
		private readonly int $taskId,
		private readonly int $userId,
		private readonly DateTime $oldDeadline,
		private readonly DateTime $newDeadline,
		private readonly string $reason = '',
		private readonly DateTime $changedAt = new DateTime()
	)
	{

	}

	public function getTaskId(): int
	{
		return $this->taskId;
	}

	public function getUserId(): int
	{
		return $this->userId;
	}

	public function getOldDeadline(): DateTime
	{
		return $this->oldDeadline;
	}

	public function getNewDeadline(): DateTime
	{
		return $this->newDeadline;
	}

	public function getReason(): string
	{
		return $this->reason;
	}

	public function getChangedAt(): DateTime
	{
		return $this->changedAt;
	}
}
