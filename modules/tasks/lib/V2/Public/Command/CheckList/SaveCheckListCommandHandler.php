<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\CheckList;

use Bitrix\Tasks\Exception;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Repository\TaskRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Service\CheckList\CheckListService;
use Bitrix\Tasks\V2\Internal\Service\Consistency\ConsistencyResolverInterface;
use Bitrix\Tasks\V2\Internal\Service\Esg\EgressInterface;
use Bitrix\Tasks\V2\Public\Provider\CheckListProvider;

class SaveCheckListCommandHandler
{
	public function __construct(
		private readonly CheckListService $checkListService,
		private readonly ConsistencyResolverInterface $consistencyResolver,
		private readonly EgressInterface $egressController,
		private readonly TaskRepositoryInterface $taskRepository,
		private readonly CheckListProvider $checkListProvider,
	)
	{
	}

	public function __invoke(SaveCheckListCommand $command): Entity\Task
	{
		$taskBeforeUpdate = $this->taskRepository->getById(
			id: $command->task->id,
		);

		if (!$taskBeforeUpdate)
		{
			throw new Exception('Task not found');
		}

		if (!is_array($command->task->checklist))
		{
			throw new Exception('Checklist needs to be provided');
		}

		$existingCheckList = $this->checkListProvider->getByEntity(
			entityId: $command->task->getId(),
			userId: $command->updatedBy,
			type: Entity\CheckList\Type::Task,
		);

		$taskBeforeUpdate = $taskBeforeUpdate->cloneWith(['checklist' => $existingCheckList->toArray()]);

		return $this->consistencyResolver->resolve('task.checklist.save')->wrap(
			function ($command, $taskBeforeUpdate): Entity\Task {

				$task = $this->checkListService->save(
					checklists: $command->task->checklist,
					taskId: $command->task->getId(),
					userId: $command->updatedBy
				);

				// append chatId
				$task = $task->cloneWith(['chatId' => $taskBeforeUpdate->chatId]);

				// notify external services about updated checklist
				$this->egressController->process(new SaveCheckListCommand(
					task: $task,
					updatedBy: $command->updatedBy,
					taskBeforeUpdate: $taskBeforeUpdate,
				));

				return $task;
			},
			[$command, $taskBeforeUpdate],
		);
	}
}
