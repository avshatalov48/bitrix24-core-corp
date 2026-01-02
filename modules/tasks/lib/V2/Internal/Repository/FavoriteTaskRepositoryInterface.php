<?php

namespace Bitrix\Tasks\V2\Internal\Repository;

interface FavoriteTaskRepositoryInterface
{
	public function getByPrimary(int $taskId, int $userId): bool;

	public function add(int $taskId, int $userId): void;

	public function delete(int $taskId, int $userId): void;
}