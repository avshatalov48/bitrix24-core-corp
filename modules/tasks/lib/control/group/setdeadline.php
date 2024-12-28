<?php

namespace Bitrix\Tasks\Control\Group;

use Bitrix\Tasks\Control\Task;

class SetDeadline
{
	public function runBatch(int $userId, array $taskIds, string $deadline): array
	{
		$result = [];
		$control = new Task($userId);

		foreach ($taskIds as $id)
		{
			$result[] = [
				$control->update($id, [
					'DEADLINE' => $deadline,
				]),
				'taskId' => $id,
			];
		}

		return $result;
	}
}
