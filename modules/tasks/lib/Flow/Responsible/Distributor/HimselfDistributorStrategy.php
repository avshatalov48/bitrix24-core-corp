<?php

namespace Bitrix\Tasks\Flow\Responsible\Distributor;

 use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\Kanban\StagesTable;

class HimselfDistributorStrategy implements DistributorStrategyInterface
{
	public function distribute(Flow $flow, array $fields, array $taskData): int
	{
		if (empty($taskData))
		{
			return $fields['CREATED_BY'];
		}

		if ($this->isTaskAddedToFlow($fields, $taskData))
		{
			return $taskData['CREATED_BY'];
		}

		if (isset($fields['HIMSELF_FLOW_TAKE_USER_ID']))
		{
			return $fields['HIMSELF_FLOW_TAKE_USER_ID'];
		}

		$responsibleId = $fields['RESPONSIBLE_ID'] ?? $taskData['RESPONSIBLE_ID'];

		return (int)$responsibleId;
	}

	private function isTaskAddedToFlow(array $fields, array $taskData): bool
	{
		if (!isset($fields['FLOW_ID']) || (int)$fields['FLOW_ID'] <= 0)
		{
			return false;
		}

		$newFlowId = (int)$fields['FLOW_ID'];
		$currentFlowId = isset($taskData['FLOW_ID']) ? (int)$taskData['FLOW_ID'] : null;

		return $currentFlowId === null || $currentFlowId !== $newFlowId;
	}
}
