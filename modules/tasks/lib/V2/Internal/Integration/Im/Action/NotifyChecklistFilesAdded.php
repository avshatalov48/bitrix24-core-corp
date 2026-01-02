<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\Im\Action;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\V2\Internal\Entity\Task;
use Bitrix\Tasks\V2\Internal\Entity\User;
use Bitrix\Tasks\V2\Internal\Integration\Im\MessageSenderInterface;

class NotifyChecklistFilesAdded
{
	public function __construct(
		Task $task,
		MessageSenderInterface $sender,
		?User $triggeredBy = null,
		int $fileCount = 1,
		string $checklistName = '',
	)
	{
		$code = 'TASKS_IM_CHECKLIST_FILES_ADDED_' . $triggeredBy?->getGender()->value;

		$message = Loc::getMessagePlural(
			$code,
			$fileCount,
			[
				'#USER#' => '[USER=' . $triggeredBy?->id . ']' . $triggeredBy?->name . '[/USER]',
				'#FILE_COUNT#' => $fileCount,
				'#CHECKLIST_NAME#' => $checklistName,
			]
		);

		$sender->sendMessage(task: $task, text: $message);
	}
}