<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\Im\Action;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\V2\Internal\Entity\Task;
use Bitrix\Tasks\V2\Internal\Entity\User;
use Bitrix\Tasks\V2\Internal\Integration\Im\MessageSenderInterface;

class NotifyTaskStatusChanged
{
	public function __construct(
		Task $task,
		MessageSenderInterface $sender,
		?User $triggeredBy = null,
		?Task\Status $newStatus = null,
	)
	{
		$replace = [
			'#TITLE#' => $task->title,
			'#USER#' => '[USER=' . $triggeredBy?->id . ']' . $triggeredBy?->name . '[/USER]'
		];

		$message = match($newStatus)
		{
			Task\Status::Completed => Loc::getMessage('TASKS_IM_TASK_STATUS_COMPLETED_' . $triggeredBy?->getGender()->value, $replace),
			Task\Status::InProgress => Loc::getMessage('TASKS_IM_TASK_STATUS_IN_PROGRESS_' . $triggeredBy?->getGender()->value, $replace),
			Task\Status::SupposedlyCompleted => Loc::getMessage('TASKS_IM_TASK_STATUS_SUSPEND_' . $triggeredBy?->getGender()->value, $replace),
			Task\Status::Deferred => Loc::getMessage('TASKS_IM_TASK_STATUS_DEFER_' . $triggeredBy?->getGender()->value, $replace),
			Task\Status::Pending => Loc::getMessage('TASKS_IM_TASK_STATUS_PENDING_' . $triggeredBy?->getGender()->value, $replace),
			default => null,
		};

		if ($message === null)
		{
			return;
		}

		$sender->sendMessage(task: $task, text: $message);
	}
}
