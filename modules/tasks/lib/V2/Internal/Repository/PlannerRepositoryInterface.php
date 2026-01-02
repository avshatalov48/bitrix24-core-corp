<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

interface PlannerRepositoryInterface
{
	public function add(int $userId, array $taskIds): void;

	public function delete(int $userId, array $taskIds): void;

	public function save(int $userId, array $taskIds): void;

	public function getAll(int $userId): ?array;
}