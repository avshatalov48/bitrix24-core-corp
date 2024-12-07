<?php

namespace Bitrix\Tasks\Flow\Kanban\Stages;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Flow\Integration\BizProc\Robot\RobotAddAccompliceCommand;
use Bitrix\Tasks\Flow\Integration\BizProc\Robot\RobotSendNotificationCommand;
use Bitrix\Tasks\Flow\Kanban\AbstractStage;
use Bitrix\Tasks\Kanban\Stage;
use Bitrix\Tasks\Kanban\StagesTable;

class ReviewStage extends AbstractStage
{
	protected function getInternalStage(): Stage
	{
		return (new Stage())
			->setTitle(Loc::getMessage('TASKS_FLOW_AUTO_CREATED_GROUP_STAGE_REVIEW'))
			->setSort(300)
			->setColor('FFAB00')
			->setSystemType(StagesTable::SYS_TYPE_REVIEW)
			->setEntityId($this->projectId)
			->setEntityType(StagesTable::WORK_MODE_GROUP);
	}

	protected function getTriggers(): array
	{
		return [];
	}

	protected function getRobots(): array
	{
		return [
			new RobotAddAccompliceCommand(
				Loc::getMessage('TASKS_FLOW_AUTO_CREATED_GROUP_STAGE_REVIEW_ROBOT_ACCOMPLICE'),
				$this->ownerId,
			),

			new RobotSendNotificationCommand(
				Loc::getMessage('TASKS_FLOW_AUTO_CREATED_GROUP_STAGE_REVIEW_ROBOT_NOTIFICATION'),
				$this->ownerId,
				Loc::getMessage('TASKS_FLOW_AUTO_CREATED_GROUP_STAGE_REVIEW_ROBOT_NOTIFICATION_MESSAGE')
			),
		];
	}
}