<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Deadline;

use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Deadline\Policy\DeadlinePolicy;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Exception\Task\UpdateDeadlineException;
use Bitrix\Tasks\V2\Internal\Repository\DeadlineChangeLogRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Service\Consistency\ConsistencyResolverInterface;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\UpdateUserFields;
use Bitrix\Tasks\V2\Internal\Service\Task\UpdateService;

class UpdateDeadlineHandler
{
	public function __construct(
		private readonly ConsistencyResolverInterface $consistencyResolver,
		private readonly UpdateService $updateService,
		private readonly DeadlinePolicy $deadlinePolicy,
		private readonly DeadlineChangeLogRepositoryInterface $deadlineChangeLogRepository,
	)
	{

	}

	public function __invoke(UpdateDeadlineCommand $updateDeadlineCommand): Entity\Task
	{
		$entity = new Entity\Task(
			id: $updateDeadlineCommand->taskId,
			deadlineTs: $updateDeadlineCommand->deadlineTs,
		);

		$userChangesCount = $this->deadlineChangeLogRepository
			->countUserChanges($updateDeadlineCommand->updateConfig->getUserId(), $entity->getId())
		;

		[$allowed, $violations] = $this->deadlinePolicy->canUpdateDeadline(
			dateTime: DateTime::createFromTimestamp($updateDeadlineCommand->deadlineTs),
			userChangesCount: $userChangesCount,
			reason: $updateDeadlineCommand->reason
		);

		if (!$allowed)
		{
			throw new UpdateDeadlineException("Cannot update deadline: " . implode(', ', $violations));
		}

		[$task, $fields] = $this->consistencyResolver->resolve('task.update')->wrap(
			function ($entity, $command): array {
				$result = $this->updateService->update($entity, $command->updateConfig);

				$this->deadlineChangeLogRepository->append(
					taskId: $entity->getId(),
					userId: $command->updateConfig->getUserId(),
					dateTime: DateTime::createFromTimestamp($command->deadlineTs),
					reason: $command->reason,
				);

				return $result;
			},
			[$entity, $updateDeadlineCommand]
		);

		// this action is outside of consistency because it is containing nested transactions
		(new UpdateUserFields($updateDeadlineCommand->updateConfig))($fields, $updateDeadlineCommand->taskId);

		return $task;
	}
}
