<?php

namespace Bitrix\Tasks\Replication\Template\Common;

use Bitrix\Main\Result;

interface HistoryServiceInterface
{
	public function write(string $message, Result $operationResult, int $taskId = 0): Result;
}