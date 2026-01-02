<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\Im\Action;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\V2\Internal\Entity\Task;
use Bitrix\Tasks\V2\Internal\Integration\Im\MessageSenderInterface;

class NotifyChatCreatedForExistingTask
{
	public function __construct(
		Task $task,
		MessageSenderInterface $sender,
		array $args = [],
	)
	{
		$message = Loc::getMessage('TASKS_IM_TASK_CHAT_CREATED_FOR_EXISTING_TASK', [
			'#TITLE#' => $task->title,
		]);

		$sender->sendMessage(
			task: $task,
			text: $message,
		);
	}
}
