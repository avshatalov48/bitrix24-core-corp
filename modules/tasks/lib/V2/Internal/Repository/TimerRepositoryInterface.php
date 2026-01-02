<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Tasks\V2\Internal\Entity;

interface TimerRepositoryInterface
{
	public function get(int $userId, int $taskId = 0): ?Entity\Task\Timer;

	public function getByUserIds(array $userIds, int $taskId): Entity\Task\TimerCollection;

	public function add(Entity\Task\Timer $timer): void;

	public function upsert(Entity\Task\Timer $timer): void;

	public function update(Entity\Task\Timer $timer): void;
}