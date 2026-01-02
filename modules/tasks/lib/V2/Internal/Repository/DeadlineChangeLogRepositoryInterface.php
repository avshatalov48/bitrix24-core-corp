<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Main\Type\DateTime;

interface DeadlineChangeLogRepositoryInterface
{
	/**
	 * Appends a new record to the log.
	 */
	public function append(
		int $taskId,
		int $userId,
		?DateTime $dateTime,
		?string $reason,
	): void;

	/**
	 * Returns total number of rows by userId and taskId.
	 */
	public function countUserChanges(int $userId, int $taskId): int;
}
