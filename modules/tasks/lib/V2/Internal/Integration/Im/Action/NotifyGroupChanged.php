<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\Im\Action;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\V2\Internal\Entity\Group;
use Bitrix\Tasks\V2\Internal\Entity\Task;
use Bitrix\Tasks\V2\Internal\Entity\User;
use Bitrix\Tasks\V2\Internal\Integration\Im\MessageSenderInterface;

class NotifyGroupChanged
{
	public function __construct(
		Task $task,
		MessageSenderInterface $sender,
		?User $triggeredBy = null,
		?Group $newGroup = null,
		?Group $oldGroup = null,
	)
	{
		$secretCode = !$newGroup?->isVisible ? 'SECRET_' : '';

		$code = 'TASKS_IM_TASK_GROUP_ADDED_' . $secretCode . $triggeredBy?->getGender()->value;

		$message = Loc::getMessage($code, [
			'#USER#' => '[USER=' . $triggeredBy?->id . ']' . $triggeredBy?->name . '[/USER]',
			'#NEW_GROUP#' => $newGroup?->name,
		]);

		if ($oldGroup !== null && $newGroup !== null)
		{
			$secretCode = !$oldGroup->isVisible || !$newGroup->isVisible ? 'SECRET_' : '';

			$code = 'TASKS_IM_TASK_GROUP_CHANGED_' . $secretCode . $triggeredBy?->getGender()->value;

			$message = Loc::getMessage($code, [
				'#USER#' => '[USER=' . $triggeredBy?->id . ']' . $triggeredBy?->name . '[/USER]',
				'#OLD_GROUP#' => $oldGroup->name,
				'#NEW_GROUP#' => $newGroup->name,
			]);
		}
		elseif ($oldGroup !== null && $newGroup === null)
		{
			$secretCode = !$oldGroup->isVisible ? 'SECRET_' : '';

			$code = 'TASKS_IM_TASK_GROUP_REMOVED_' . $secretCode . $triggeredBy?->getGender()->value;

			$message = Loc::getMessage($code, [
				'#USER#' => '[USER=' . $triggeredBy?->id . ']' . $triggeredBy?->name . '[/USER]',
				'#GROUP#' => $oldGroup->name,
			]);
		}

		$sender->sendMessage(task: $task, text: $message);
	}
}
