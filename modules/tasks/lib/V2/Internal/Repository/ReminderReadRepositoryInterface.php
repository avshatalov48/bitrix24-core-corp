<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\V2\Internal\Entity\Task\Reminder;
use Bitrix\Tasks\V2\Internal\Entity\Task\ReminderCollection;

interface ReminderReadRepositoryInterface
{
	public function getById(int $id): ?Reminder;

	public function getByTaskId(int $taskId, int $userId, int $offset, int $limit): ReminderCollection;

	public function getByDate(DateTime $reminderData, int $limit = 50): ReminderCollection;

	public function getRecalculableDeadlineReminders(int $taskId): ReminderCollection;

	public function getRecurringReminders(int $taskId): ReminderCollection;
}