<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Audit;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Config\UpdateConfig;
use Bitrix\Tasks\V2\Internal\Repository\TaskMemberRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Service\UpdateTaskService;

class WatchTaskHandler
{
	public function __construct(
		private readonly TaskMemberRepositoryInterface $memberRepository,
		private readonly UpdateTaskService $updateTaskService,
	)
	{

	}

	public function __invoke(WatchTaskCommand $command): Entity\UserCollection
	{
		$auditors = $this->memberRepository->getAuditors($command->taskId);
		if ($auditors->findOneById($command->auditorId))
		{
			return $auditors;
		}

		$auditors->add(Entity\User::mapFromId($command->auditorId));

		$task = new Entity\Task(
			id: $command->taskId,
			auditors: $auditors
		);

		$config = new UpdateConfig(
			userId: $command->userId,
			skipNotifications: $command->skipNotification,
		);

		$taskAfter = $this->updateTaskService->update(
			task: $task,
			config: $config,
		);

		return $taskAfter->auditors;
	}
}
