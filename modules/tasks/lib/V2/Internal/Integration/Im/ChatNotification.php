<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\Im;

use Bitrix\Main\Loader;
use Bitrix\Tasks\V2\Internal\Entity\Task;
use Bitrix\Main\Localization\Loc;

class ChatNotification implements ChatNotificationInterface, MessageSenderInterface
{
	/**
	 * Generic notification method.
	 * @param NotificationType $type
	 * @param Task $task
	 * @param array $args Additional arguments for replacements (e.g., triggeredBy, oldResponsible, etc.)
	 */
	public function notify(NotificationType $type, Task $task, array $args = []): void
	{
		$this->loadMessages();

		match ($type) {
			NotificationType::ChatCreatedForExistingTask => new Action\NotifyChatCreatedForExistingTask(
				task: $task,
				sender: $this,
				args: $args,
			),
			NotificationType::TaskHasForumComments => new Action\NotifyTaskHasForumComments(
				task: $task,
				sender: $this,
				args: $args
			),
			NotificationType::TaskHasLegacyChat => new Action\NotifyTaskHasLegacyChat(
				task: $task,
				sender: $this,
				args: $args,
			),
			NotificationType::TaskCreated => new Action\NotifyTaskCreated(
				task: $task,
				sender: $this,
				triggeredBy: $args['triggeredBy'] ?? null
			),
			NotificationType::ResponsibleChanged => new Action\NotifyResponsibleChanged(
				task: $task,
				sender: $this,
				triggeredBy: $args['triggeredBy'] ?? null,
				oldResponsible: $args['oldResponsible'] ?? null,
				newResponsible: $args['newResponsible'] ?? null
			),
			NotificationType::OwnerChanged => new Action\NotifyOwnerChanged(
				task: $task,
				sender: $this,
				triggeredBy: $args['triggeredBy'] ?? null,
				oldOwner: $args['oldOwner'] ?? null,
				newOwner: $args['newOwner'] ?? null
			),
			NotificationType::DeadlineChanged => new Action\NotifyDeadlineChanged(
				task: $task,
				sender: $this,
				triggeredBy: $args['triggeredBy'] ?? null,
				newDeadlineTs: $args['newDeadlineTs'] ?? null,
				oldDeadlineTs: $args['oldDeadlineTs'] ?? null
			),
			NotificationType::AuditorsChanged => new Action\NotifyAuditorsChanged(
				task: $task,
				sender: $this,
				triggeredBy: $args['triggeredBy'] ?? null,
				oldAuditors: $args['oldAuditors'] ?? null,
				newAuditors: $args['newAuditors'] ?? null
			),
			NotificationType::AccomplicesChanged => new Action\NotifyAccomplicesChanged(
				task: $task,
				sender: $this,
				triggeredBy: $args['triggeredBy'] ?? null,
				oldAccomplices: $args['oldAccomplices'] ?? null,
				newAccomplices: $args['newAccomplices'] ?? null
			),
			NotificationType::GroupChanged => new Action\NotifyGroupChanged(
				task: $task,
				sender: $this,
				triggeredBy: $args['triggeredBy'] ?? null,
				newGroup: $args['newGroup'] ?? null,
				oldGroup: $args['oldGroup'] ?? null
			),
			NotificationType::TaskOverdue => new Action\NotifyTaskOverdue(
				task: $task,
				sender: $this,
				args: $args,
			),
			NotificationType::TaskStatusChanged => new Action\NotifyTaskStatusChanged(
				task: $task,
				sender: $this,
				triggeredBy: $args['triggeredBy'] ?? null,
				newStatus: $args['newStatus'] ?? null
			),
			NotificationType::TaskTimerStarted => new Action\NotifyTaskTimerStarted(
				task: $task,
				sender: $this,
				triggeredBy: $args['triggeredBy'] ?? null
			),
			NotificationType::TaskTimerStopped => new Action\NotifyTaskTimerStopped(
				task: $task,
				sender: $this,
				triggeredBy: $args['triggeredBy'] ?? null
			),
			NotificationType::ChecklistItemsAdded => new Action\NotifyChecklistItemsAdded(
				task: $task,
				sender: $this,
				triggeredBy: $args['triggeredBy'] ?? null,
				itemCount: $args['itemCount'] ?? 1,
				checklistName: $args['checklistName'] ?? ''
			),
			NotificationType::ChecklistItemsDeleted => new Action\NotifyChecklistItemsDeleted(
				task: $task,
				sender: $this,
				triggeredBy: $args['triggeredBy'] ?? null,
				itemCount: $args['itemCount'] ?? 1,
				checklistName: $args['checklistName'] ?? ''
			),
			NotificationType::ChecklistItemsModified => new Action\NotifyChecklistItemsModified(
				task: $task,
				sender: $this,
				triggeredBy: $args['triggeredBy'] ?? null,
				itemCount: $args['itemCount'] ?? 1,
				checklistName: $args['checklistName'] ?? ''
			),
			NotificationType::ChecklistItemsCompleted => new Action\NotifyChecklistItemsCompleted(
				task: $task,
				sender: $this,
				triggeredBy: $args['triggeredBy'] ?? null,
				itemCount: $args['itemCount'] ?? 1,
				checklistName: $args['checklistName'] ?? ''
			),
			NotificationType::ChecklistItemsUnchecked => new Action\NotifyChecklistItemsUnchecked(
				task: $task,
				sender: $this,
				triggeredBy: $args['triggeredBy'] ?? null,
				itemCount: $args['itemCount'] ?? 1,
				checklistName: $args['checklistName'] ?? ''
			),
			NotificationType::ChecklistSingleItemCompleted => new Action\NotifyChecklistSingleItemCompleted(
				task: $task,
				sender: $this,
				triggeredBy: $args['triggeredBy'] ?? null,
				checklistName: $args['checklistName'] ?? '',
				itemName: $args['itemName'] ?? ''
			),
			NotificationType::ChecklistSingleItemUnchecked => new Action\NotifyChecklistSingleItemUnchecked(
				task: $task,
				sender: $this,
				triggeredBy: $args['triggeredBy'] ?? null,
				checklistName: $args['checklistName'] ?? '',
				itemName: $args['itemName'] ?? ''
			),
			NotificationType::ChecklistAuditorAssigned => new Action\NotifyChecklistAuditorAssigned(
				task: $task,
				sender: $this,
				triggeredBy: $args['triggeredBy'] ?? null,
				checklistName: $args['checklistName'] ?? '',
				assignee: $args['assignee'] ?? null
			),
			NotificationType::ChecklistAccompliceAssigned => new Action\NotifyChecklistAccompliceAssigned(
				task: $task,
				sender: $this,
				triggeredBy: $args['triggeredBy'] ?? null,
				checklistName: $args['checklistName'] ?? '',
				assignee: $args['assignee'] ?? null
			),
			NotificationType::ChecklistFilesAdded => new Action\NotifyChecklistFilesAdded(
				task: $task,
				sender: $this,
				triggeredBy: $args['triggeredBy'] ?? null,
				fileCount: $args['fileCount'] ?? 1,
				checklistName: $args['checklistName'] ?? ''
			),
			NotificationType::ChecklistCompleted => new Action\NotifyChecklistCompleted(
				task: $task,
				sender: $this,
				triggeredBy: $args['triggeredBy'] ?? null,
				checklistName: $args['checklistName'] ?? ''
			),
			NotificationType::ChecklistGroupedOperations => new Action\NotifyChecklistGroupedOperations(
				task: $task,
				sender: $this,
				triggeredBy: $args['triggeredBy'] ?? null,
				operations: $args
			),
			NotificationType::ChecklistAdded => new Action\NotifyChecklistAdded(
				task: $task,
				sender: $this,
				triggeredBy: $args['triggeredBy'] ?? null,
				itemCount: $args['itemCount'] ?? 1,
				checklistName: $args['checklistName'] ?? ''
			),
			NotificationType::ChecklistDeleted => new Action\NotifyChecklistDeleted(
				task: $task,
				sender: $this,
				triggeredBy: $args['triggeredBy'] ?? null,
				checklistName: $args['checklistName'] ?? ''
			),
			default => null,
		};
	}

	public function sendMessage(Task $task, string|null $text): void
	{
		if (!Loader::includeModule('im'))
		{
			return;
		}

		if ($text === null)
		{
			return;
		}

		if ($task->chatId === null)
		{
			return;
		}

		$authorId = 0; // system user

		$chat = \Bitrix\Im\V2\Chat::getInstance($task->chatId);
		$message = (new \Bitrix\Im\V2\Message())
			->setMessage($text)
			->setAuthorId($authorId)
		;
		$chat->sendMessage($message);
	}

	private function loadMessages(): void
	{
		Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/tasks/lib/V2/Internal/Integration/Im/ChatNotification.php');
	}
}
