<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task;

use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Service\AddTaskService;

class AddTaskHandler
{
	public function __construct(
		private readonly AddTaskService $addTaskService,
	)
	{
	}

	public function __invoke(AddTaskCommand $command): Entity\Task
	{
		return $this->addTaskService->add(
			task: $command->task,
			config: $command->config,
		);
	}
}
