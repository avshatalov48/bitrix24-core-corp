<?php

namespace Bitrix\Crm\Kanban\Entity\ActivityStages;

use Bitrix\Crm\Kanban\Entity\EntityActivities;
use Bitrix\Main\ArgumentException;

class Factory
{
	public static function getStageInstance(string $stageId): AbstractStage
	{
		return match ($stageId)
		{
			EntityActivities::STAGE_OVERDUE => new Overdue(),
			EntityActivities::STAGE_PENDING => new Pending(),
			EntityActivities::STAGE_THIS_WEEK => new Week(),
			EntityActivities::STAGE_NEXT_WEEK => new NextWeek(),
			EntityActivities::STAGE_IDLE => new Idle(),
			EntityActivities::STAGE_LATER => new Later(),
			EntityActivities::STAGE_COMPLETED => new Completed(),
			default => new ArgumentException('Unknown stageId: ' . $stageId),
		};
	}
}
