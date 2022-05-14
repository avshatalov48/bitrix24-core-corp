<?php

namespace Bitrix\Tasks\Control;

use Bitrix\Tasks\Internals\Task\FavoriteTable;

class Favorite
{
	private $userId;

	public function __construct(int $userId)
	{
		$this->userId = $userId;
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
			['CHECK_EXISTENCE' => false]
		);
	}

	/**
	 * @param int $taskId
	 * @return bool
	 */
	public function isInFavorite(int $taskId): bool
	{
		$inFavorite = FavoriteTable::check([
			'TASK_ID' => $taskId,
			'USER_ID' => $this->userId
		]);

		return $inFavorite !== false;
	}
}