<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Status;

use Bitrix\Tasks\V2\Internal\Entity\Task;
use Bitrix\Tasks\V2\Internal\Service\Task\StatusService;

class DisapproveTaskHandler
{
	public function __construct(
		private readonly StatusService $statusService,
	)
	{

	}

	public function __invoke(DisapproveTaskCommand $command): Task
	{
		return $this->statusService->disapprove($command->taskId, $command->config);
	}
}
