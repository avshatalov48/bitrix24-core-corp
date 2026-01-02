<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\Im\Action;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\V2\Internal\Entity\Task;
use Bitrix\Tasks\V2\Internal\Entity\User;
use Bitrix\Tasks\V2\Internal\Integration\Im\MessageSenderInterface;

class NotifyChecklistDeleted
{
	public function __construct(
		Task $task,
		MessageSenderInterface $sender,
		?User $triggeredBy = null,
		string $checklistName = '',
	)
	{
		$code = 'TASKS_IM_CHECKLIST_DELETED_' . $triggeredBy?->getGender()->value;

		$message = Loc::getMessage(
			$code,
			[
				'#USER#' => '[USER=' . $triggeredBy?->id . ']' . $triggeredBy?->name . '[/USER]',
				'#CHECKLIST_NAME#' => $checklistName,
			]
		);

		$sender->sendMessage(task: $task, text: $message);
	}
}