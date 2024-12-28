<?php

namespace Bitrix\Tasks\Control\Group;

use Bitrix\Tasks\Control\Task;

class Delete
{
	public function runBatch(int $userId, array $taskIds): array
	{
		$result = [];
		$control = new Task($userId);

		foreach ($taskIds as $id)
		{
			$result[] = [
				$control->delete($id),
				'taskId' => $id,
			];
		}

		return $result;
	}
}
