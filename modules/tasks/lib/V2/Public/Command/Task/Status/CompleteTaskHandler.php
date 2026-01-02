<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Status;

use Bitrix\Tasks\V2\Internal\Service\Task\StatusService;
use Bitrix\Tasks\V2\Internal\Entity;

class CompleteTaskHandler
{
	public function __construct(
		private readonly StatusService $statusService,
	)
	{

	}

	public function __invoke(CompleteTaskCommand $command): Entity\Task
	{
		return $this->statusService->complete($command->taskId, $command->config);
	}
}
