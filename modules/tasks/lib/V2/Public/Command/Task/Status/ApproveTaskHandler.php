<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Status;

use Bitrix\Tasks\V2\Internal\Entity\Task;
use Bitrix\Tasks\V2\Internal\Service\Task\StatusService;

class ApproveTaskHandler
{
	public function __construct(
		private readonly StatusService $statusService,
	)
	{

	}

	public function __invoke(ApproveTaskCommand $command): Task
	{
		return $this->statusService->approve($command->taskId, $command->config);
	}
}
