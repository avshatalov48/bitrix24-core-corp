<?php

namespace Bitrix\Tasks\Flow\Kanban\Stages;

use Bitrix\Tasks\Flow\Kanban\AbstractStage;
use Bitrix\Tasks\Flow\Kanban\SystemType;

class StageFactory
{
	public static function get(SystemType $type, int $projectId, int $ownerId, int $flowId, int $stageId): AbstractStage
	{
		return match ($type)
		{
			SystemType::NEW => new NewStage($projectId, $ownerId, $flowId, $stageId),
			SystemType::PROGRESS => new ProgressStage($projectId, $ownerId, $flowId, $stageId),
			SystemType::REVIEW => new ReviewStage($projectId, $ownerId, $flowId, $stageId),
			SystemType::COMPLETED => new CompletedStage($projectId, $ownerId, $flowId, $stageId),
		};
	}
}