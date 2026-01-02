<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task;

use Bitrix\Tasks\V2\Internal\Entity\Task;
use Bitrix\Tasks\V2\Internal\Entity\Task\Status;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Config\UpdateConfig;
use Bitrix\Tasks\V2\Internal\Service\UpdateTaskService;

class StatusService
{
	public function __construct(
		private readonly UpdateTaskService $updateTaskService,
		private readonly StatusResolver $statusResolver,
	)
	{

	}

	public function start(int $taskId, UpdateConfig $config): Task
	{
		return $this->updateTaskStatus($taskId, Status::InProgress, $config);
	}

	public function renew(int $taskId, UpdateConfig $config): Task
	{
		return $this->updateTaskStatus($taskId, Status::Pending, $config);
	}

	public function complete(int $taskId, UpdateConfig $config): Task
	{
		$status = $this->statusResolver->resolveForComplete($taskId, $config->getUserId());
		
		return $this->updateTaskStatus($taskId, $status, $config);
	}

	public function approve(int $taskId, UpdateConfig $config): Task
	{
		return $this->updateTaskStatus($taskId, Status::Completed, $config);
	}

	public function pause(int $taskId, UpdateConfig $config): Task
	{
		return $this->updateTaskStatus($taskId, Status::Pending, $config);
	}

	public function defer(int $taskId, UpdateConfig $config): Task
	{
		return $this->updateTaskStatus($taskId, Status::Deferred, $config);
	}

	public function disapprove(int $taskId, UpdateConfig $config): Task
	{
		return $this->updateTaskStatus($taskId, Status::Pending, $config);
	}

	private function updateTaskStatus(int $taskId, Status $status, UpdateConfig $config): Task
	{
		$entity = new Task(
			id: $taskId,
			status: $status
		);

		return $this->updateTaskService->update($entity, $config);
	}
}
