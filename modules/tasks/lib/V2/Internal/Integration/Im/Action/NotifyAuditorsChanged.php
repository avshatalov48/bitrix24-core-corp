<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\Im\Action;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\V2\Internal\Entity\Task;
use Bitrix\Tasks\V2\Internal\Entity\User;
use Bitrix\Tasks\V2\Internal\Entity\UserCollection;
use Bitrix\Tasks\V2\Internal\Integration\Im\MessageSenderInterface;

class NotifyAuditorsChanged
{
	public function __construct(
		Task $task,
		MessageSenderInterface $sender,
		?User $triggeredBy = null,
		?UserCollection $oldAuditors = null,
		?UserCollection $newAuditors = null,
	)
	{
		$oldAuditorsNames = [];
		if ($oldAuditors !== null)
		{
			foreach ($oldAuditors as $user)
			{
				$oldAuditorsNames[] = '[USER=' . $user->id . ']' . $user->name . '[/USER]';
			}
		}

		$newAuditorsNames = [];
		if ($newAuditors !== null)
		{
			foreach ($newAuditors as $user)
			{
				$newAuditorsNames[] = '[USER=' . $user->id . ']' . $user->name . '[/USER]';
			}
		}

		$newDiff = array_diff($newAuditorsNames, $oldAuditorsNames);
		$oldDiff = array_diff($oldAuditorsNames, $newAuditorsNames);

		if (!empty($newDiff))
		{
			$code = 'TASKS_IM_TASK_AUDITORS_NEW_' . $triggeredBy?->getGender()->value;

			$message = Loc::getMessage($code, [
				'#USER#' => '[USER=' . $triggeredBy?->id . ']' . $triggeredBy?->name . '[/USER]',
				'#NEW_AUDITORS#' => implode(', ', $newDiff),
			]);

			$sender->sendMessage(task: $task, text: $message);
		}

		if (!empty($oldDiff))
		{
			$code = 'TASKS_IM_TASK_AUDITORS_REMOVE_' . $triggeredBy?->getGender()->value;

			$message = Loc::getMessage($code, [
				'#USER#' => '[USER=' . $triggeredBy?->id . ']' . $triggeredBy?->name . '[/USER]',
				'#OLD_AUDITORS#' => implode(', ', $oldDiff),
			]);

			$sender->sendMessage(task: $task, text: $message);
		}
	}
}
