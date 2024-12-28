<?php

namespace Bitrix\Tasks\Control\Group;

use Bitrix\Tasks\Control;
use Bitrix\Tasks\Integration\Socialnetwork\Task;

class Favorite
{
	public function add(int $userId, array $taskIds): void
	{
		$favorite = new Control\Favorite($userId);

		foreach ($taskIds as $id)
		{
			if ($favorite->isInFavorite($id))
			{
				continue;
			}

			$favorite->add($id);

			Task::toggleFavorites([
				'TASK_ID' => $id,
				'USER_ID' => $userId,
				'OPERATION' => 'ADD',
			]);
		}
	}

	public function remove(int $userId, array $taskIds): void
	{
		$favorite = new Control\Favorite($userId);

		foreach ($taskIds as $id)
		{
			$favorite->delete($id);

			Task::toggleFavorites([
				'TASK_ID' => $id,
				'USER_ID' => $userId,
				'OPERATION' => 'DELETE',
			]);
		}
	}
}
