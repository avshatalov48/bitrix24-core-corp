<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Esg\Handler;

use Bitrix\Tasks\V2\Internal\Entity\Task;
use Bitrix\Tasks\V2\Internal\Entity\User;
use Bitrix\Tasks\V2\Internal\Integration\Im\ChatNotificationInterface;
use Bitrix\Tasks\V2\Internal\Integration\Im\NotificationType;
use Bitrix\Tasks\V2\Internal\Integration\Im\Chat;
use Bitrix\Tasks\V2\Internal\Repository\UserRepositoryInterface;
use Bitrix\Tasks\V2\Public\Command\Task\UpdateTaskCommand;

class TaskUpdateHandler
{
	public function __construct(
		private readonly Chat                    $chatIntegration,
		private readonly UserRepositoryInterface $userRepository,
		private readonly ChatNotificationInterface $chatNotification
	)
	{
	}

	public function handle(UpdateTaskCommand $command): void
	{
		$taskBeforeUpdate = $command->taskBeforeUpdate;

		if ($taskBeforeUpdate === null)
		{
			return;
		}

		$changes = $command->task->diff($taskBeforeUpdate);
		$triggeredBy = $this->userRepository
			->getByIds([$command->config->getUserId()])
			->findOneById($command->config->getUserId())
		;

		foreach ($changes as $key => $change)
		{
			match ($key)
			{
				'responsible' => $this->handleTaskMembersChanged(
					task: $command->task,
					triggeredBy: $triggeredBy,
					taskBeforeUpdate: $taskBeforeUpdate,
					key: 'responsible',
				),
				'creator' => $this->handleTaskMembersChanged(
					task: $command->task,
					triggeredBy: $triggeredBy,
					taskBeforeUpdate: $taskBeforeUpdate,
					key: 'creator',
				),
				'deadlineTs' => $this->chatNotification->notify(
					type: NotificationType::DeadlineChanged,
					task: $command->task,
					args: ['triggeredBy' => $triggeredBy, 'oldDeadlineTs' => $taskBeforeUpdate->deadlineTs, 'newDeadlineTs' => $command->task->deadlineTs],
				),
				'auditors' => $this->handleTaskMembersChanged(
					task: $command->task,
					triggeredBy: $triggeredBy,
					taskBeforeUpdate: $taskBeforeUpdate,
					key: 'auditors',
				),
				'accomplices' => $this->handleTaskMembersChanged(
					task: $command->task,
					triggeredBy: $triggeredBy,
					taskBeforeUpdate: $taskBeforeUpdate,
					key: 'accomplices',
				),
				'group' => $this->chatNotification->notify(
					type: NotificationType::GroupChanged,
					task: $command->task,
					args: ['triggeredBy' => $triggeredBy, 'oldGroup' => $taskBeforeUpdate->group, 'newGroup' => $command->task->group],
				),
				'status' => $this->chatNotification->notify(
					type: NotificationType::TaskStatusChanged,
					task: $command->task,
					args: ['triggeredBy' => $triggeredBy, 'oldStatus' => $taskBeforeUpdate->status, 'newStatus' => $command->task->status],
				),
				default => '',
			};
		}
	}

	protected function handleTaskMembersChanged(Task $task, ?User $triggeredBy, Task $taskBeforeUpdate, string $key): void
	{
		match ($key)
		{
			'responsible' => $this->chatNotification->notify(
				type: NotificationType::ResponsibleChanged,
				task: $task,
				args: ['triggeredBy' => $triggeredBy, 'oldResponsible' => $taskBeforeUpdate->responsible, 'newResponsible' => $task->responsible],
			),
			'creator' => $this->chatNotification->notify(
				type: NotificationType::OwnerChanged,
				task: $task,
				args: ['triggeredBy' => $triggeredBy, 'oldOwner' => $taskBeforeUpdate->creator, 'newOwner' => $task->creator],
			),
			'auditors' => $this->chatNotification->notify(
				type: NotificationType::AuditorsChanged,
				task: $task,
				args: ['triggeredBy' => $triggeredBy, 'oldAuditors' => $taskBeforeUpdate->auditors, 'newAuditors' => $task->auditors],
			),
			'accomplices' => $this->chatNotification->notify(
				type: NotificationType::AccomplicesChanged,
				task: $task,
				args: ['triggeredBy' => $triggeredBy, 'oldAccomplices' => $taskBeforeUpdate->accomplices, 'newAccomplices' => $task->accomplices],
			),
			default => false,
		};

		$membersToAdd = array_values(array_diff($task->getMemberIds(), $taskBeforeUpdate->getMemberIds()));
		$membersToHide = array_values(array_diff($taskBeforeUpdate->getMemberIds(), $task->getMemberIds()));

		$this->chatIntegration->addChatMembers(task: $task, membersToAdd: $membersToAdd);
		$this->chatIntegration->hideChatMembers(task: $task, membersToHide: $membersToHide);
	}
}
