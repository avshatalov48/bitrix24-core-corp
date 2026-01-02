<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service;

use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\V2\Internal\Integration\Forum;
use Bitrix\Tasks\V2\Internal\Integration\Im\LegacyChat;

class TaskLegacyFeatureService
{
	public function hasForumComments(int $taskId): bool
	{
		$task = TaskRegistry::getInstance()->getObject($taskId);

		if (!$task)
		{
			return false;
		}

		$topicId = $task->getForumTopicId();

		if (!$topicId)
		{
			return false;
		}

		return (new Forum\Message())->hasTopicComments($topicId);
	}

	public function getLegacyChatId(int $taskId): ?int
	{
		$task = TaskRegistry::getInstance()->getObject($taskId);

		if (!$task)
		{
			return null;
		}

		return (new LegacyChat())->getTaskChatId($taskId);
	}
}
