<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\Im\Action;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\Tasks\V2\Internal\Entity\Task;
use Bitrix\Tasks\V2\Internal\Entity\User;
use Bitrix\Tasks\V2\Internal\Integration\Im\MessageSenderInterface;

class NotifyDeadlineChanged
{
	public function __construct(
		Task $task,
		MessageSenderInterface $sender,
		?User $triggeredBy = null,
		?int $newDeadlineTs = null,
		?int $oldDeadlineTs = null,
	)
	{
		$code = 'TASKS_IM_TASK_DEADLINE_ADDED_' . $triggeredBy?->getGender()->value;

		$message = Loc::getMessage($code, [
			'#USER#' => '[USER=' . $triggeredBy?->id . ']' . $triggeredBy?->name . '[/USER]',
			'#NEW_DEADLINE#' => DateTime::createFromTimestamp($newDeadlineTs)->format('Y-m-d H:i'),
		]);

		if ($oldDeadlineTs !== null && $newDeadlineTs !== null) {
			$code = 'TASKS_IM_TASK_DEADLINE_CHANGED_' . $triggeredBy?->getGender()->value;

			$message = Loc::getMessage($code, [
				'#USER#' => '[USER=' . $triggeredBy?->id . ']' . $triggeredBy?->name . '[/USER]',
				'#OLD_DEADLINE#' => DateTime::createFromTimestamp($oldDeadlineTs)->format('Y-m-d H:i'),
				'#NEW_DEADLINE#' => DateTime::createFromTimestamp($newDeadlineTs)->format('Y-m-d H:i'),
			]);
		} elseif ($oldDeadlineTs !== null && $newDeadlineTs === null) {
			$code = 'TASKS_IM_TASK_DEADLINE_REMOVED_' . $triggeredBy?->getGender()->value;

			$message = Loc::getMessage($code, [
				'#USER#' => '[USER=' . $triggeredBy?->id . ']' . $triggeredBy?->name . '[/USER]',
			]);
		}

		$sender->sendMessage(task: $task, text: $message);
	}
}
