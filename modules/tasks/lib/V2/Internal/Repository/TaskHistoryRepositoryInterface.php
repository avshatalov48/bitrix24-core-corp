<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Tasks\V2\Internal\Entity\HistoryLogCollection;

interface TaskHistoryRepositoryInterface
{
	public function tail(int $taskId, int $offset = 0): HistoryLogCollection;
}