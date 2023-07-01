<?php

namespace Bitrix\Crm\Badge\Type;

use Bitrix\Crm\Activity\Provider\Tasks\TaskActivityStatus;
use Bitrix\Crm\Badge\Badge;
use Bitrix\Crm\Badge\ValueItem;
use Bitrix\Crm\Badge\ValueItemOptions;
use Bitrix\Main\Localization\Loc;

class TaskStatus extends Badge
{
	protected const TYPE = 'task_status';

	public function getFieldName(): string
	{
		return Loc::getMessage('CRM_BADGE_TASK_STATUS_FIELD_NAME');
	}

	public function getValuesMap(): array
	{
		$taskStatus = new TaskActivityStatus();
		return [
			new ValueItem(
				TaskActivityStatus::STATUS_CREATED,
				$taskStatus->getStatusLocMessage(TaskActivityStatus::STATUS_CREATED),
				ValueItemOptions::TEXT_COLOR_SECONDARY,
				ValueItemOptions::BG_COLOR_SECONDARY
			),
			new ValueItem(
				TaskActivityStatus::STATUS_VIEWED,
				$taskStatus->getStatusLocMessage(TaskActivityStatus::STATUS_VIEWED),
				ValueItemOptions::TEXT_COLOR_PRIMARY,
				ValueItemOptions::BG_COLOR_PRIMARY
			),
			new ValueItem(
				TaskActivityStatus::STATUS_UPDATED,
				$taskStatus->getStatusLocMessage(TaskActivityStatus::STATUS_UPDATED),
				ValueItemOptions::TEXT_COLOR_SECONDARY,
				ValueItemOptions::BG_COLOR_SECONDARY
			),
			new ValueItem(
				TaskActivityStatus::STATUS_IN_PROGRESS,
				$taskStatus->getStatusLocMessage(TaskActivityStatus::STATUS_IN_PROGRESS),
				ValueItemOptions::TEXT_COLOR_PRIMARY,
				ValueItemOptions::BG_COLOR_PRIMARY
			),
			new ValueItem(
				TaskActivityStatus::STATUS_WAITING,
				$taskStatus->getStatusLocMessage(TaskActivityStatus::STATUS_WAITING),
				ValueItemOptions::TEXT_COLOR_PRIMARY,
				ValueItemOptions::BG_COLOR_PRIMARY
			),
			new ValueItem(
				TaskActivityStatus::STATUS_DEADLINE_CHANGED,
				$taskStatus->getStatusLocMessage(TaskActivityStatus::STATUS_DEADLINE_CHANGED),
				ValueItemOptions::TEXT_COLOR_PRIMARY,
				ValueItemOptions::BG_COLOR_PRIMARY
			),
			new ValueItem(
				TaskActivityStatus::STATUS_RESULT_ADDED,
				$taskStatus->getStatusLocMessage(TaskActivityStatus::STATUS_RESULT_ADDED),
				ValueItemOptions::TEXT_COLOR_PRIMARY,
				ValueItemOptions::BG_COLOR_PRIMARY
			),
			new ValueItem(
				TaskActivityStatus::STATUS_EXPIRED,
				$taskStatus->getStatusLocMessage(TaskActivityStatus::STATUS_EXPIRED),
				ValueItemOptions::TEXT_COLOR_FAILURE,
				ValueItemOptions::BG_COLOR_FAILURE
			),
			new ValueItem(
				TaskActivityStatus::STATUS_CONTROL_WAITING,
				$taskStatus->getStatusLocMessage(TaskActivityStatus::STATUS_CONTROL_WAITING),
				ValueItemOptions::TEXT_COLOR_PRIMARY,
				ValueItemOptions::BG_COLOR_PRIMARY
			),
			new ValueItem(
				TaskActivityStatus::STATUS_FINISHED,
				$taskStatus->getStatusLocMessage(TaskActivityStatus::STATUS_FINISHED),
				ValueItemOptions::TEXT_COLOR_SUCCESS,
				ValueItemOptions::BG_COLOR_SUCCESS
			),
		];
	}

	public function getType(): string
	{
		return self::TYPE;
	}
}
