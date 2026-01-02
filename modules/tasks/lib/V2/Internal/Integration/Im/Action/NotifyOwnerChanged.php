<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\Im\Action;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\V2\Internal\Entity\Task;
use Bitrix\Tasks\V2\Internal\Entity\User;
use Bitrix\Tasks\V2\Internal\Integration\Im\MessageSenderInterface;

class NotifyOwnerChanged
{
	public function __construct(
		Task $task,
		MessageSenderInterface $sender,
		?User $triggeredBy = null,
		?User $oldOwner = null,
		?User $newOwner = null,
	)
	{
		$code = 'TASKS_IM_TASK_OWNER_CHANGED_' . $triggeredBy?->getGender()->value;

		$message = Loc::getMessage($code, [
			'#USER#' => '[USER=' . $triggeredBy?->id . ']' . $triggeredBy?->name . '[/USER]',
			'#OLD_OWNER#' => '[USER=' . $oldOwner?->id . ']' . $oldOwner?->name . '[/USER]',
			'#NEW_OWNER#' => '[USER=' . $newOwner?->id . ']' . $newOwner?->name . '[/USER]',
		]);

		$sender->sendMessage(task: $task, text: $message);
	}
}
