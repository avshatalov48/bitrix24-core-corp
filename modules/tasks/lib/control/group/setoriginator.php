<?php

namespace Bitrix\Tasks\Control\Group;

use Bitrix\Tasks\Control\Task;

class SetOriginator
{
	public function runBatch(int $userId, array $taskIds, int $originatorId, ?int $responsibleId = null): array
	{
		$result = [];
		$control = new Task($userId);

		$fields = [
			'CREATED_BY' => $originatorId,
		];

		if ($responsibleId !== null)
		{
			$fields['RESPONSIBLE_ID'] = $responsibleId;
		}

		foreach ($taskIds as $id)
		{
			$result[] = [
				$control->update($id, $fields),
				'taskId' => $id,
			];
		}

		return $result;
	}
}
