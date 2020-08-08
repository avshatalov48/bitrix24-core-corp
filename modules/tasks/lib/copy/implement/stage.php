<?php
namespace Bitrix\Tasks\Copy\Implement;

use Bitrix\Main\Copy\Container;
use Bitrix\Tasks\Kanban\TaskStageTable;

class Stage
{
	private $taskImplementer;
	private $mapIdsCopiedStages = [];

	public function __construct(Base $taskImplementer, $mapIdsCopiedStages)
	{
		$this->taskImplementer = $taskImplementer;
		$this->mapIdsCopiedStages = $mapIdsCopiedStages;
	}

	public function getStageIds($taskId)
	{
		$stageIds = [];

		$queryObject = TaskStageTable::getList(["select" => ["ID", "STAGE_ID"], "filter" => ["TASK_ID" => $taskId]]);
		while ($stage = $queryObject->fetch())
		{
			$stageIds[$stage["ID"]] = $stage["STAGE_ID"];
		}

		return $stageIds;
	}

	public function addStages($taskId, array $stageIds)
	{
		$result = [];

		foreach ($stageIds as $oldId => $stageId)
		{
			$fields = [
				"TASK_ID" => $taskId,
				"STAGE_ID" => $stageId
			];
			if (!TaskStageTable::getList(["filter" => $fields])->fetch())
			{
				$addResult = TaskStageTable::add($fields);
				$result[$oldId] = ($addResult->isSuccess() ? $addResult->getId() : false);
			}
		}

		return $result;
	}

	public function updateTaskStageId(Container $container, $taskId, $copiedTaskId)
	{
		$fields = $this->taskImplementer->getFields($container, $taskId);
		if ($fields["STAGE_ID"] && array_key_exists($fields["STAGE_ID"], $this->mapIdsCopiedStages))
		{
			$this->taskImplementer->update($copiedTaskId, [
				"STAGE_ID" => $this->mapIdsCopiedStages[$fields["STAGE_ID"]]]);
		}
	}
}