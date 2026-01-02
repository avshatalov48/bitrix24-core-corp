<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\Im\Action;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\V2\Internal\Entity\Task;
use Bitrix\Tasks\V2\Internal\Entity\User;
use Bitrix\Tasks\V2\Internal\Integration\Im\MessageSenderInterface;

class NotifyTaskCreated
{
	public function __construct(
		Task $task,
		MessageSenderInterface $sender,
		?User $triggeredBy = null,
	)
	{
		$code = 'TASKS_IM_TASK_CREATED_' . $triggeredBy?->getGender()->value;
		$creatorId = $task->creator?->id ?? null;
		$creatorName = $task->creator?->name ?? '';

		$message = Loc::getMessage($code, [
			'#USER#' => '[USER='. $creatorId . ']' . $creatorName . '[/USER]',
			'#TITLE#' => $task->title,
		]);

		$sender->sendMessage(task: $task, text: $message);
	}
}
