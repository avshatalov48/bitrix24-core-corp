<?php

namespace Bitrix\Tasks\Control\Group;

use Bitrix\Tasks\Control\Task;

class SetGroup
{
	public function runBatch(int $userId, array $taskIds, int $groupId): array
	{
		$result = [];
		$control = new Task($userId);

		foreach ($taskIds as $id)
		{
			$result[] = [
				$control->update($id, [
					'GROUP_ID' => $groupId,
				]),
				'taskId' => $id,
			];
		}

		return $result;
	}
}
