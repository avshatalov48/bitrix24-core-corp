<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\Im\Action;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\V2\Internal\Entity\Task;
use Bitrix\Tasks\V2\Internal\Entity\User;
use Bitrix\Tasks\V2\Internal\Integration\Im\MessageSenderInterface;

class NotifyResponsibleChanged
{
	public function __construct(
		Task $task,
		MessageSenderInterface $sender,
		?User $triggeredBy = null,
		?User $oldResponsible = null,
		?User $newResponsible = null,
	)
	{
		$code = 'TASKS_IM_TASK_RESPONSIBLE_CHANGED_' . $triggeredBy?->getGender()->value;

		$message = Loc::getMessage($code, [
			'#USER#' => '[USER='. $triggeredBy?->id . ']' . $triggeredBy?->name . '[/USER]',
			'#OLD_RESPONSIBLE#' => '[USER='. $oldResponsible?->id . ']' . $oldResponsible?->name . '[/USER]',
			'#NEW_RESPONSIBLE#' => '[USER='. $newResponsible?->id .']' . $newResponsible?->name . '[/USER]',
		]);

		$sender->sendMessage(task: $task, text: $message);
	}
}
