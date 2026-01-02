<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\Im\Action;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\V2\Internal\Entity\Task;
use Bitrix\Tasks\V2\Internal\Entity\User;
use Bitrix\Tasks\V2\Internal\Integration\Im\MessageSenderInterface;

class NotifyTaskTimerStarted
{
	public function __construct(
		Task $task,
		MessageSenderInterface $sender,
		?User $triggeredBy = null,
	)
	{
		$code = 'TASKS_IM_TASK_TIMER_STARTED_' . $triggeredBy?->getGender()->value;

		$message = Loc::getMessage($code, [
			'#TITLE#' => $task->title,
			'#USER#' => '[USER=' . $triggeredBy?->id . ']' . $triggeredBy?->name . '[/USER]'
		]);

		$sender->sendMessage(task: $task, text: $message);
	}
}
