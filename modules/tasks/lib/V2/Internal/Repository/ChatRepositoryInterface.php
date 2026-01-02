<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

interface ChatRepositoryInterface
{
	public function getChatIdByTaskId(int $taskId): ?int;
	public function save(int $chatId, int $taskId): void;
	public function delete(int $taskId): void;
}