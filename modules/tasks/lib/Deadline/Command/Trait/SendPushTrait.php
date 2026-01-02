<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Deadline\Command\Trait;

use Bitrix\Tasks\Integration\Pull\PushService;

trait SendPushTrait
{
	private function sendPush(int $userId, string $command, array $params = []): void
	{
		PushService::addEvent($userId, [
			'module_id' => 'tasks',
			'command' => $command,
			'params' => $params,
		]);
	}
}
