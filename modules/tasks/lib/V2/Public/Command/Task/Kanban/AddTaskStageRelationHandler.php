<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Kanban;

use Bitrix\Tasks\V2\Internal\Service\Task\TaskStageService;

class AddTaskStageRelationHandler
{
	public function __construct(
		private readonly TaskStageService $taskStageService,
	)
	{

	}
	public function __invoke(AddTaskStageRelationCommand $command): int
	{
		return $this->taskStageService->upsert($command->taskId, $command->stageId);
	}
}
