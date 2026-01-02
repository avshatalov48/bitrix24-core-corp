<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\EventHandler;

use Bitrix\Tasks\Internals\Task\ReminderTable;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Entity\Task;

final class Reminder
{
	public static function onTaskUpdate(Task $before, Task $after): void
	{
		$reminderRepository = Container::getInstance()->getReminderRepository();

		if ($after->status === Task\Status::Completed && $after->status !== $before->status)
		{
			$reminderRepository->deleteByFilter(['=TASK_ID' => $after->getId()]);

			return;
		}

		if ($before->deadlineTs > 0 && !$after->deadlineTs)
		{
			$reminderRepository->deleteByFilter(['=TASK_ID' => $after->getId(), '=TYPE' => ReminderTable::TYPE_DEADLINE]);

			return;
		}

		if ($before->deadlineTs && $after->deadlineTs && $before->deadlineTs !== $after->deadlineTs)
		{
			$reminderService = Container::getInstance()->getReminderService();

			$reminderService->recalculateDeadlineRemindersByTaskId($after->getId(), $after->deadlineTs);
		}
	}
}