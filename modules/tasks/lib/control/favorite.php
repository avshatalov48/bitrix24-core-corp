<?php

namespace Bitrix\Tasks\Control;

use Bitrix\Tasks\Internals\Task\FavoriteTable;

class Favorite
{
	public function __construct(private int $userId)
	{
	}

	/**
	 * @param int $taskId
	 * @return void
	 * @throws \Exception
	 */
	public function add(int $taskId): void
	{
		FavoriteTable::add(
			[
				'TASK_ID' => $taskId,
				'USER_ID' => $this->userId
			],
			['CHECK_EXISTENCE' => false],
		);
	}

	/**
	 * @param int $taskId
	 * @return void
	 * @throws \Exception
	 */
	public function delete(int $taskId): void
	{
		FavoriteTable::delete(
			[
				'TASK_ID' => $taskId,
				'USER_ID' => $this->userId
			],
			['CHECK_EXISTENCE' => false]
		);
	}

	/**
	 * @param int $taskId
	 * @return bool
	 */
	public function isInFavorite(int $taskId): bool
	{
		if ($taskId <= 0)
		{
			return false;
		}

		$inFavorite = FavoriteTable::check([
			'TASK_ID' => $taskId,
			'USER_ID' => $this->userId
		]);

		return $inFavorite !== false;
	}
}
