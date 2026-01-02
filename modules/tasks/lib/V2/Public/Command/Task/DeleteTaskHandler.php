<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task;

use Bitrix\Tasks\V2\Internal\Service\DeleteTaskService;

class DeleteTaskHandler
{
	public function __construct(
		private readonly DeleteTaskService $deleteTaskService,
	)
	{

	}

	public function __invoke(DeleteTaskCommand $command): void
	{
		$this->deleteTaskService->delete(
			taskId: $command->taskId,
			config: $command->config,
		);
	}
}
