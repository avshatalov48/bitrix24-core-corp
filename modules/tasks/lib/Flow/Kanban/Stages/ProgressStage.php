<?php

namespace Bitrix\Tasks\Flow\Kanban\Stages;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Flow\Integration\BizProc\Robot\RobotStatusChangedCommand;
use Bitrix\Tasks\Flow\Integration\BizProc\Trigger\TriggerCommand;
use Bitrix\Tasks\Flow\Kanban\AbstractStage;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\Kanban\Stage;
use Bitrix\Tasks\Kanban\StagesTable;

class ProgressStage extends AbstractStage
{
	protected function getInternalStage(): Stage
	{
		return (new Stage())
			->setTitle(Loc::getMessage('TASKS_FLOW_AUTO_CREATED_GROUP_STAGE_PROGRESS'))
			->setSort(200)
			->setColor(StagesTable::SYS_TYPE_PROGRESS_COLOR)
			->setSystemType(StagesTable::SYS_TYPE_PROGRESS)
			->setEntityId($this->projectId)
			->setEntityType(StagesTable::WORK_MODE_GROUP);
	}

	protected function getTriggers(): array
	{
		return [
			new TriggerCommand(
				Loc::getMessage('TASKS_FLOW_AUTO_CREATED_GROUP_STAGE_PROGRESS_TRIGGER'),
				Status::IN_PROGRESS
			),
		];
	}

	protected function getRobots(): array
	{
		return [
			new RobotStatusChangedCommand(
				Loc::getMessage('TASKS_FLOW_AUTO_CREATED_GROUP_STAGE_PROGRESS_ROBOT'),
				Status::IN_PROGRESS
			)
		];
	}
}