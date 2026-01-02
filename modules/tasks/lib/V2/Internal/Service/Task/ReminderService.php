<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task;

use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Collection;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\V2\Internal\Access\Service\TaskRightService;
use Bitrix\Tasks\V2\Internal\Entity\Task;
use Bitrix\Tasks\V2\Internal\Entity\Task\Reminder;
use Bitrix\Tasks\V2\Internal\Entity\UserCollection;
use Bitrix\Tasks\V2\Internal\Exception\Task\ReminderException;
use Bitrix\Tasks\V2\Internal\Repository\Mapper\ReminderMapper;
use Bitrix\Tasks\V2\Internal\Repository\ReminderReadRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\ReminderRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\Task\Select;
use Bitrix\Tasks\V2\Internal\Repository\TaskReadRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\UserRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Result\Result;
use Bitrix\Tasks\V2\Internal\Service\Link\LinkService;
use Bitrix\Tasks\V2\Internal\Service\Recurring\RRuleService;
use CEvent;
use CTaskNotifications;

class ReminderService
{
	public function __construct(
		private readonly LinkService $linkService,
		private readonly TaskRightService $taskRightService,
		private readonly RRuleService $rruleService,
		private readonly TaskReadRepositoryInterface $taskReadRepository,
		private readonly UserRepositoryInterface $userRepository,
		private readonly ReminderRepositoryInterface $reminderRepository,
		private readonly ReminderReadRepositoryInterface $reminderReadRepository,
		private readonly ReminderMapper $reminderMapper,
	)
	{

	}

	public function addMulti(Task\ReminderCollection $reminders): void
	{
		foreach ($reminders as $reminder)
		{
			$this->add($reminder);
		}
	}

	public function add(Reminder $reminder): int
	{
		$task = $this->taskReadRepository->getById($reminder->taskId);
		if ($task === null || $task->status === Task\Status::Completed)
		{
			throw new ReminderException('Task not found or closed');
		}

		$nextTimeTs = $this->calculateNextRemindTs($reminder);

		$reminder = $reminder->cloneWith(['nextRemindTs' => $nextTimeTs]);

		$id = $this->reminderRepository->save($reminder);

		$fields = $this->reminderMapper->mapFromEntity($reminder);

		foreach (GetModuleEvents('tasks', 'OnTaskReminderAdd', true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, [$id, $fields]);
		}

		return $id;
	}

	public function recalculateDeadlineRemindersByTaskId(int $taskId, int $deadlineTs): void
	{
		$task = $this->taskReadRepository->getById($taskId);
		if ($task === null || $task->deadlineTs === null)
		{
			return;
		}

		$reminders = $this->reminderReadRepository->getRecalculableDeadlineReminders($taskId);
		if ($reminders->isEmpty())
		{
			return;
		}

		$newReminders = [];
		foreach ($reminders as $reminder)
		{
			$nextRemindTs = $deadlineTs - $reminder->before;
			if ($nextRemindTs === $reminder->nextRemindTs)
			{
				continue;
			}

			$newReminders[] = $reminder->cloneWith(['nextRemindTs' =>  $deadlineTs - $reminder->before]);
		}

		$this->reminderRepository->saveBatch(new Task\ReminderCollection(...$newReminders));
	}

	public function send(Reminder $reminder): Result
	{
		$result = new Result();

		$task = $this->taskReadRepository->getById((int)$reminder->taskId, new Select(members: true));
		if ($task === null)
		{
			return $result->addError(new Error('Failed to find task'));
		}

		if (!$this->canSendReminderByStatus($task))
		{
			return $result;
		}

		$recipients = $this->getRecipients($reminder, $task);
		if ($recipients->isEmpty())
		{
			return $result->addError(new Error('Failed to find recipients'));
		}

		if ($reminder->remindVia === Reminder\RemindVia::Notification)
		{
			return $this->sendNotification($recipients, $task);
		}

		if ($reminder->remindVia === Reminder\RemindVia::Email)
		{
			return $this->sendEmail($recipients, $task);
		}

		return $result;
	}

	public function recalculate(Task\ReminderCollection $reminders): void
	{
		$toDelete = new Task\ReminderCollection();
		$toRecalculate = new Task\ReminderCollection();

		foreach ($reminders as $reminder)
		{
			if ($reminder->remindBy !== Reminder\RemindBy::Recurring)
			{
				$toDelete->add($reminder);

				continue;
			}

			$task = $this->taskReadRepository->getById($reminder->taskId);
			if ($task === null || !$this->canSendReminderByStatus($task))
			{
				$toDelete->add($reminder);

				continue;
			}

			$toRecalculate->add($reminder);
		}

		if (!$toDelete->isEmpty())
		{
			$this->reminderRepository->deleteByFilter(['@ID' => $toDelete->getIdList()]);
		}

		if (!$toRecalculate->isEmpty())
		{
			$this->recalculateRecurringReminders($toRecalculate);
		}
	}

	private function recalculateRecurringReminders(Task\ReminderCollection $reminders): void
	{
		$newReminders = [];
		foreach ($reminders as $reminder)
		{
			$nextRemindTs = $this->rruleService->getNextDate($reminder->nextRemindTs, $reminder->rrule);

			$newReminders[] = $reminder->cloneWith(['nextRemindTs' => $nextRemindTs]);
		}

		$this->reminderRepository->saveBatch(new Task\ReminderCollection(...$newReminders));
	}

	private function getRecipients(Reminder $reminder, Task $task): UserCollection
	{
		$recipients = [];
		if ($reminder->recipient === Reminder\Recipient::Myself)
		{
			$recipients = [$reminder->userId];
		}
		elseif ($reminder->recipient === Reminder\Recipient::Creator)
		{
			$recipients = [$task->creator?->getId()];
		}
		elseif ($reminder->recipient === Reminder\Recipient::Responsible)
		{
			$recipients = [$task->responsible?->getId()];
		}
		elseif ($reminder->recipient === Reminder\Recipient::Accomplice)
		{
			$recipients = (array)$task->accomplices?->getIdList();
		}

		Collection::normalizeArrayValuesByInt($recipients, false);

		$this->taskRightService->getUserRightBatch(ActionDictionary::ACTION_TASK_READ, $reminder->taskId, $recipients);

		return $this->userRepository->getByIds($recipients);
	}

	private function sendNotification(UserCollection $users, Task $task): Result
	{
		$result = new Result();

		if (!Loader::includeModule('im'))
		{
			return $result->addError(new Error('IM module is not loaded'));
		}

		$reminderMessage = str_replace(['#TASK_TITLE#'], [$task->title], Loc::getMessage('TASKS_REMINDER_REMINDER'));

		$sent = CTaskNotifications::sendMessageEx(
			$task->getId(),
			$task->creator?->getId(),
			$users->getIdList(),
			[
				'INSTANT' => $reminderMessage,
				'EMAIL' => $reminderMessage,
				'PUSH' => $reminderMessage,
			],
			[
				'NOTIFY_EVENT' => 'reminder',
				'EXCLUDE_USERS_WITH_MUTE' => 'N',
			]
		);

		if (!$sent)
		{
			return $result->addError(new Error('Failed to send notification'));
		}

		return $result;
	}

	private function sendEmail(UserCollection $users, Task $task): Result
	{
		$result = new Result();

		foreach ($users as $user)
		{
			$event = [
				'PATH_TO_TASK' => $this->linkService->getWithServer($task->getId(), $user->getId()),
				'TASK_TITLE' => $task->title,
				'EMAIL_TO' => $user->email,
			];

			$sent = CEvent::Send('TASK_REMINDER', $task->siteId, $event, 'N');

			if (!$sent)
			{
				$result->addError(new Error('Failed to send email reminder'));
			}
		}

		return $result;
	}

	private function calculateNextRemindTs(Reminder $reminder): int
	{
		if ($reminder->remindBy === Reminder\RemindBy::Deadline && $reminder->before > 0)
		{
			$task = $this->taskReadRepository->getById($reminder->taskId);
			if ($task === null || $task->deadlineTs === null)
			{
				throw new ReminderException('Task not found or no deadline');
			}

			return $task->deadlineTs - $reminder->before;

		}

		if ($reminder->remindBy === Reminder\RemindBy::Recurring && $reminder->rrule !== null)
		{
			return $this->rruleService->getNextDate(time(), $reminder->rrule);
		}

		if ($reminder->nextRemindTs !== null)
		{
			return $reminder->nextRemindTs;
		}

		throw new ReminderException('Wrong remind time');
	}

	private function canSendReminderByStatus(Task $task): bool
	{
		return $task->status !== Task\Status::Completed;
	}
}
