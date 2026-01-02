<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Audit;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Config\UpdateConfig;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\UpdateUserFields;
use Bitrix\Tasks\V2\Internal\Repository\TaskMemberRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Service\UpdateTaskService;

class UnwatchTaskHandler
{
	public function __construct(
		private readonly TaskMemberRepositoryInterface $memberRepository,
		private readonly UpdateTaskService $updateTaskService,
	)
	{

	}

	public function __invoke(UnwatchTaskCommand $command): Entity\UserCollection
	{
		$auditors = $this->memberRepository->getAuditors($command->taskId);
		if (!$auditors->findOneById($command->auditorId))
		{
			return $auditors;
		}

		$auditors->remove($command->auditorId);

		$task = new Entity\Task(
			id: $command->taskId,
			auditors: $auditors
		);

		$config = new UpdateConfig(
			userId: $command->userId
		);

		$taskAfter = $this->updateTaskService->update(
			task: $task,
			config: $config,
		);

		return $taskAfter->auditors;
	}
}
