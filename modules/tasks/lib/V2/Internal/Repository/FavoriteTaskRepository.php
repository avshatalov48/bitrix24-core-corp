<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Main\DB\DuplicateEntryException;
use Bitrix\Main\Event;
use Bitrix\Tasks\Internals\Task\FavoriteTable;
use Bitrix\Tasks\V2\Internal\Exception\Task\TaskFavoriteException;

class FavoriteTaskRepository implements FavoriteTaskRepositoryInterface
{
	public function getByPrimary(int $taskId, int $userId): bool
	{
		$data = FavoriteTable::getById([
			'TASK_ID' => $taskId,
			'USER_ID' => $userId]
		)->fetch();

		return $data !== false;
	}

	public function add(int $taskId, int $userId): void
	{
		try
		{
			$result = FavoriteTable::add([
				'TASK_ID' => $taskId,
				'USER_ID' => $userId,
			], [
				'CHECK_EXISTENCE' => false,
				'AFFECT_CHILDREN' => false,
			]);
		}
		catch (DuplicateEntryException)
		{
			return;
		}

		if (!$result->isSuccess())
		{
			throw new TaskFavoriteException($result->getError()?->getMessage());
		}
	}

	public function delete(int $taskId, int $userId): void
	{
		$result = FavoriteTable::delete([
			'TASK_ID' => $taskId,
			'USER_ID' => $userId,
		]);

		if (!$result->isSuccess())
		{
			throw new TaskFavoriteException($result->getError()?->getMessage());
		}
	}
}