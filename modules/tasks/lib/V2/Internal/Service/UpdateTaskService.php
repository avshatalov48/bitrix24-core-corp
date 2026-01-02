<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service;

use Bitrix\Main\Command\Exception\CommandValidationException;
use Bitrix\Tasks\Control\Exception\TaskNotExistsException;
use Bitrix\Tasks\Control\Exception\TaskUpdateException;
use Bitrix\Tasks\Control\Exception\WrongTaskIdException;
use Bitrix\Tasks\V2\Internal\Entity\Task;
use Bitrix\Tasks\V2\Internal\Repository\TaskRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Service\Consistency\ConsistencyResolverInterface;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\AddUserFields;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Config\AddConfig;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Config\UpdateConfig;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\UpdateUserFields;
use Bitrix\Tasks\V2\Internal\Service\Task\AddService;
use Bitrix\Tasks\V2\Internal\Service\Task\UpdateService;

class UpdateTaskService
{
	public function __construct(
		private readonly UpdateService $updateService,
		private readonly ConsistencyResolverInterface $consistencyResolver,
		private readonly TaskRepositoryInterface $taskRepository,
	)
	{

	}

	/**
	 * @throws TaskNotExistsException
	 * @throws CommandValidationException
	 * @throws WrongTaskIdException
	 * @throws TaskUpdateException
	 */
	public function update(Task $task, UpdateConfig $config, bool $useConsistency = true): Task
	{
		if ($task->id <= 0)
		{
			throw new WrongTaskIdException();
		}

		if ($useConsistency)
		{
			[$task, $fields] = $this->consistencyResolver->resolve('task.update')->wrap(
				fn (): array => $this->updateService->update($task, $config)
			);
		}
		else
		{
			[$task, $fields] = $this->updateService->update($task, $config);
		}

		if ((new UpdateUserFields($config))($fields, $task->id))
		{
			$this->taskRepository->invalidate($task->id);
		}

		return $this->taskRepository->getById($task->id);
	}
}
