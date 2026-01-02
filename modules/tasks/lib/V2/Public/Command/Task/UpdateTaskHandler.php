<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task;

use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Service\UpdateTaskService;

class UpdateTaskHandler
{
	public function __construct(
		private readonly UpdateTaskService $updateTaskService,
	)
	{

	}

	public function __invoke(UpdateTaskCommand $command): Entity\Task
	{
		return $this->updateTaskService->update(
			task: $command->task,
			config: $command->config,
		);
	}
}
