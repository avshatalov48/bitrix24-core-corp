<?php

namespace Bitrix\Tasks\Control\Group;

use Bitrix\Tasks\Control\Task;

class SetTaskControl
{
	public function runBatch(int $userId, array $taskIds, string $state): array
	{
		$result = [];
		$control = new Task($userId);

		foreach ($taskIds as $id)
		{
			$result[] = [
				$control->update($id, [
					'TASK_CONTROL' => $state,
				]),
				'taskId' => $id,
			];
		}

		return $result;
	}
}
