<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Esg\Handler;

use RuntimeException;
use Bitrix\Tasks\V2\Internal\Entity\Task;
use Bitrix\Tasks\V2\Internal\Integration\Im\Chat;
use Bitrix\Tasks\V2\Internal\Integration\Im\ChatNotificationInterface;
use Bitrix\Tasks\V2\Internal\Integration\Im\NotificationType;
use Bitrix\Tasks\V2\Internal\Repository\UserRepositoryInterface;
use Bitrix\Tasks\V2\Public\Command\Task\AddTaskCommand;

class TaskAddHandler
{
	public function __construct(
		private readonly Chat $chatIntegration,
		private readonly UserRepositoryInterface $userRepository,
		private readonly ChatNotificationInterface $chatNotification
	)
	{
	}

	public function handle(AddTaskCommand $command): Task
	{
		// create new chat
		$chatId = $this->chatIntegration->addChat($command->task);

		if ($chatId === null)
		{
			throw new RuntimeException('There was an error while saving task chat');
		}

		$createdTask = $command->task->cloneWith(['chatId' => $chatId]);

		$this->chatNotification->notify(
			type: NotificationType::TaskCreated,
			task: $createdTask,
			args: ['triggeredBy' => $this->userRepository
				->getByIds([$command->config->getUserId()])
				->findOneById($command->config->getUserId())
			],
		);

		return $createdTask;
	}
}
