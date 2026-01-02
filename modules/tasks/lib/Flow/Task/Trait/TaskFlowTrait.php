<?php

namespace Bitrix\Tasks\Flow\Task\Trait;

use Bitrix\Tasks\Flow\Task\Status;

trait TaskFlowTrait
{
	private function isTaskAddedToFlow(array $fields, array $taskData): bool
	{
		$newFlowId = (int)($fields['FLOW_ID'] ?? 0);
		if ($newFlowId <= 0)
		{
			return false;
		}

		$currentFlowId = (int)($taskData['FLOW_ID'] ?? 0);

		return ($currentFlowId <= 0) || ($currentFlowId !== $newFlowId);
	}

	private function isTaskStatusNew(array $taskData): bool
	{
		return isset($taskData['REAL_STATUS'])
			&& in_array($taskData['REAL_STATUS'], [Status::NEW, Status::PENDING]);
	}
}
