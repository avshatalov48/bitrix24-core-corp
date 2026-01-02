<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Esg\Handler;

use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Entity\Task;
use Bitrix\Tasks\V2\Internal\Integration\Im\Chat;
use Bitrix\Tasks\V2\Internal\Integration\Im\ChatNotificationInterface;
use Bitrix\Tasks\V2\Internal\Integration\Im\NotificationType;
use RuntimeException;

class CreateChatForExistingTaskHandler
{
	public function __construct(
		private readonly Chat $chatIntegration,
		private readonly ChatNotificationInterface $chatNotification
	)
	{
	}

	public function handle(Task $task): Task
	{
		// create new chat
		$chatId = $this->chatIntegration->addChat($task);

		if ($chatId === null)
		{
			throw new RuntimeException('There was an error while creating new task chat');
		}

		$updatedTask = $task->cloneWith(['chatId' => $chatId]);

		$this->chatNotification->notify(
			type: NotificationType::ChatCreatedForExistingTask,
			task: $updatedTask,
		);

		$taskLegacyFeatureService = Container::getInstance()->getTaskLegacyFeatureService();

		if ($taskLegacyFeatureService->hasForumComments($task->getId()))
		{
			$this->chatNotification->notify(
				type: NotificationType::TaskHasForumComments,
				task: $updatedTask
			);
		}

		$legacyChatId = $taskLegacyFeatureService->getLegacyChatId($task->getId());

		if ($legacyChatId)
		{
			$this->chatNotification->notify(
				type: NotificationType::TaskHasLegacyChat,
				task: $updatedTask,
				args: ['chatId' => $legacyChatId],
			);
		}

		return $updatedTask;
	}
}
