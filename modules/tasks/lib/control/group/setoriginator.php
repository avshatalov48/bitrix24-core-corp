<?php

namespace Bitrix\Tasks\Control\Group;

use Bitrix\Tasks\Control\Task;

class SetOriginator
{
	public function runBatch(int $userId, array $taskIds, int $originatorId): array
	{
		$result = [];
		$control = new Task($userId);

		foreach ($taskIds as $id)
		{
			$result[] = [
				$control->update($id, [
					'CREATED_BY' => $originatorId,
				]),
				'taskId' => $id,
			];
		}

		return $result;
	}
}
