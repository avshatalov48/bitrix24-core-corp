<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task;

use Bitrix\Tasks\V2\Internal\Entity\HistoryLog;
use Bitrix\Tasks\V2\Internal\Repository\TaskLogRepositoryInterface;

class HistoryService
{
	public function __construct(
		private readonly TaskLogRepositoryInterface $taskLogRepository,
	)
	{

	}

	public function add(HistoryLog $historyLog): void
	{
		$this->taskLogRepository->add($historyLog);
	}
}
