<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service;

use Bitrix\Tasks\Internals\Counter;

class CounterService
{
	public function collect(int $taskId): void
	{
		Counter\CounterService::getInstance()->collectData($taskId);
	}

	public function addEvent(string $type, array $parameters = []): void
	{
		Counter\CounterService::addEvent(
			$type,
			$parameters,
		);
	}
}