<?php

namespace Bitrix\Tasks\Replication\Template\Common\Service;

use Bitrix\Main\Result;
use Bitrix\Tasks\Replication\Template\Common\HistoryServiceInterface;

class DefaultHistoryService implements HistoryServiceInterface
{
	public function write(string $message, Result $operationResult, int $taskId = 0): Result
	{
		return new Result();
	}
}