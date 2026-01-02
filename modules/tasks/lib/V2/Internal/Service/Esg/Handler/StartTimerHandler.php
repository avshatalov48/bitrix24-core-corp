<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Esg\Handler;

use Bitrix\Tasks\V2\Internal\Integration\Im\ChatNotificationInterface;
use Bitrix\Tasks\V2\Internal\Integration\Im\NotificationType;
use Bitrix\Tasks\V2\Internal\Repository\TaskReadRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\UserRepositoryInterface;
use Bitrix\Tasks\V2\Public\Command\Task\Tracking\StartTimerCommand;

class StartTimerHandler
{
	public function __construct(
		private readonly TaskReadRepositoryInterface $taskReadRepository,
		private readonly UserRepositoryInterface $userRepository,
		private readonly ChatNotificationInterface $chatNotification
	)
	{
	}

	public function handle(StartTimerCommand $command): void
	{
		$task = $this->taskReadRepository->getById($command->taskId);

		if (!$task)
		{
			return;
		}

		$triggeredBy = $this->userRepository->getByIds([$command->userId])->findOneById($command->userId);

		$this->chatNotification->notify(
			type: NotificationType::TaskTimerStarted,
			task: $task,
			args: ['triggeredBy' => $triggeredBy],
		);
	}
}
