<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\Im\Action;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\V2\Internal\Entity\Task;
use Bitrix\Tasks\V2\Internal\Entity\User;
use Bitrix\Tasks\V2\Internal\Integration\Im\MessageSenderInterface;

class NotifyChecklistSingleItemCompleted
{
	public function __construct(
		Task $task,
		MessageSenderInterface $sender,
		?User $triggeredBy = null,
		string $checklistName = '',
		string $itemName = '',
	)
	{
		$code = 'TASKS_IM_CHECKLIST_SINGLE_ITEM_COMPLETED_' . $triggeredBy?->getGender()->value;

		$message = Loc::getMessage($code, [
			'#USER#' => '[USER=' . $triggeredBy?->id . ']' . $triggeredBy?->name . '[/USER]',
			'#ITEM_NAME#' => $itemName,
			'#CHECKLIST_NAME#' => $checklistName,
		]);

		$sender->sendMessage(task: $task, text: $message);
	}
}