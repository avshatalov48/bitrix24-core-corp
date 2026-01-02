<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\Im\Action;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\V2\Internal\Entity\Task;
use Bitrix\Tasks\V2\Internal\Entity\User;
use Bitrix\Tasks\V2\Internal\Integration\Im\MessageSenderInterface;

class NotifyChecklistItemsAdded
{
	public function __construct(
		Task $task,
		MessageSenderInterface $sender,
		?User $triggeredBy = null,
		int $itemCount = 1,
		string $checklistName = '',
	)
	{
		$code = 'TASKS_IM_CHECKLIST_ITEMS_ADDED_' . $triggeredBy?->getGender()->value;

		$message = Loc::getMessagePlural(
			$code,
			$itemCount,
			[
				'#USER#' => '[USER=' . $triggeredBy?->id . ']' . $triggeredBy?->name . '[/USER]',
				'#ITEM_COUNT#' => $itemCount,
				'#CHECKLIST_NAME#' => $checklistName,
			]
		);

		$sender->sendMessage(task: $task, text: $message);
	}
}