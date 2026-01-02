<?php

namespace Bitrix\Tasks\Flow\Responsible\Distributor;

use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\Task\Trait\TaskFlowTrait;
use Bitrix\Tasks\Internals\Task\Status;

class HimselfDistributorStrategy implements DistributorStrategyInterface
{
	use TaskFlowTrait;

	public function distribute(Flow $flow, array $fields, array $taskData): int
	{
		$isNewTask = empty($taskData);
		if ($isNewTask)
		{
			return (int)($fields['CREATED_BY'] ?? 0);
		}

		$isPendingTask = (int)($taskData['REAL_STATUS'] ?? 0) === Status::PENDING;
		if ($isPendingTask && $this->isTaskAddedToFlow($fields, $taskData))
		{
			return (int)($fields['CREATED_BY'] ?? $taskData['CREATED_BY']);
		}

		$newFlowId = (int)($fields['FLOW_ID'] ?? 0);
		$currentFlowId = (int)($taskData['FLOW_ID'] ?? 0);

		if ($newFlowId === $currentFlowId)
		{
			return (int)$taskData['RESPONSIBLE_ID'];
		}

		return (int)($fields['RESPONSIBLE_ID'] ?? $taskData['RESPONSIBLE_ID']);
	}
}
