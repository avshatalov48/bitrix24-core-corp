<?php

namespace Bitrix\Tasks\Control\Group;

use Bitrix\Tasks\Control\Task;

class SetFlow
{
	public function runBatch(int $userId, array $taskIds, int $flowId): array
	{
		$result = [];
		$control = new Task($userId);

		foreach ($taskIds as $id)
		{
			$result[] = [
				$control->update($id, [
					'FLOW_ID' => $flowId,
				]),
				'taskId' => $id,
			];
		}

		return $result;
	}
}
