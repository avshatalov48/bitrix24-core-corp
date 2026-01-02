<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Status;

use Bitrix\Tasks\V2\Internal\Entity\Task;
use Bitrix\Tasks\V2\Internal\Service\Task\StatusService;

class PauseTaskHandler
{
	public function __construct(
		private readonly StatusService $statusService,
	)
	{

	}

	public function __invoke(PauseTaskCommand $command): Task
	{
		return $this->statusService->pause($command->taskId, $command->config);
	}
}
