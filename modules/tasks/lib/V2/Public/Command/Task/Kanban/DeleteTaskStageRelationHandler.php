<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Kanban;

use Bitrix\Tasks\V2\Internal\Service\Task\TaskStageService;

class DeleteTaskStageRelationHandler
{
	public function __construct(
		private readonly TaskStageService $taskStageService,
	)
	{

	}
	public function __invoke(DeleteTaskStageRelationCommand $command): void
	{
		$this->taskStageService->clearRelations($command->relationIds);
	}
}
