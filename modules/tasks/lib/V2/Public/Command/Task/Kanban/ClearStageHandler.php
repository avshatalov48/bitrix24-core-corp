<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Kanban;

use Bitrix\Tasks\V2\Internal\Repository\TaskStageRepositoryInterface;

class ClearStageHandler
{
	public function __construct(
		private readonly TaskStageRepositoryInterface $taskStageRepository,
	)
	{

	}

	public function __invoke(ClearStageCommand $command): void
	{
		$this->taskStageRepository->deleteByStageId($command->stageId);
	}
}