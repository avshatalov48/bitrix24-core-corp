<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Kanban;

use Bitrix\Tasks\V2\Internal\Service\Task\TaskStageService;

class MoveTaskHandler
{
	public function __construct(
		private readonly TaskStageService $stageService,
	)
	{

	}

	public function __invoke(MoveTaskCommand $command): void
	{
		$this->stageService->move($command->relationId, $command->stageId);
	}
}