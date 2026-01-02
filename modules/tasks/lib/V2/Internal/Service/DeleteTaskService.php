<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service;

use Bitrix\Tasks\Control\Exception\TaskNotExistsException;
use Bitrix\Tasks\Control\Exception\TaskStopDeleteException;
use Bitrix\Tasks\Control\Exception\WrongTaskIdException;
use Bitrix\Tasks\V2\Internal\Service\Consistency\ConsistencyResolverInterface;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Config\DeleteConfig;
use Bitrix\Tasks\V2\Internal\Service\Task\DeleteService;

class DeleteTaskService
{
	public function __construct(
		private readonly ConsistencyResolverInterface $consistencyResolver,
		private readonly DeleteService $deleteService,
	)
	{

	}

	/**
	 * @throws TaskNotExistsException
	 * @throws WrongTaskIdException
	 * @throws TaskStopDeleteException
	 */
	public function delete(int $taskId, DeleteConfig $config, bool $useConsistency = true): void
	{
		if ($useConsistency)
		{
			$this->consistencyResolver->resolve('task.delete')->wrap(
				fn () => $this->deleteService->delete($taskId, $config)
			);
		}
		else
		{
			$this->deleteService->delete($taskId, $config);
		}
	}
}
