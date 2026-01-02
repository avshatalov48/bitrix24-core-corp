<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\Im\Action;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\V2\Internal\Entity\Task;
use Bitrix\Tasks\V2\Internal\Integration\Im\MessageSenderInterface;

class NotifyTaskHasForumComments
{
	public function __construct(
		Task $task,
		MessageSenderInterface $sender,
		array $args = [],
	)
	{
		$url = '/task/comments/' . $task->getId() . '/';

		$message = Loc::getMessage('TASKS_IM_TASK_HAS_FORUM_COMMENTS', [
			'#URL_BEGIN#' => '[URL=' . $url . ']',
			'#URL_END#' => '[/URL]',
		]);

		$sender->sendMessage(
			task: $task,
			text: $message,
		);
	}
}
