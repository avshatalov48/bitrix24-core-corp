<?php

namespace Bitrix\Tasks\Control\Group;

use Bitrix\Tasks\Control\Task;

class SetResponsible
{
	public function runBatch(int $userId, array $taskIds, int $responsibleId): array
	{
		$result = [];
		$control = new Task($userId);

		foreach ($taskIds as $id)
		{
			$result[] = [
				$control->update($id, [
					'RESPONSIBLE_ID' => $responsibleId,
				]),
				'taskId' => $id,
			];
		}

		return $result;
	}
}
