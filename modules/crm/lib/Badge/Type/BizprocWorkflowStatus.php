<?php

namespace Bitrix\Crm\Badge\Type;

use Bitrix\Crm\Badge\Badge;
use Bitrix\Crm\Badge\ValueItem;
use Bitrix\Crm\Badge\ValueItemOptions;
use Bitrix\Main\Localization\Loc;

class BizprocWorkflowStatus extends Badge
{
	public const RUNNING_TASK_VALUE = 'running_task';
	public const DONE_VALUE = 'done';

	public function getValuesMap(): array
	{
		return [
			new ValueItem(
				self::RUNNING_TASK_VALUE,
				Loc::getMessage('CRM_BADGE_BIZPROC_WORKFLOW_STATUS_TASK_RUNNING') ?? '',
				ValueItemOptions::TEXT_COLOR_PRIMARY,
				ValueItemOptions::BG_COLOR_PRIMARY,
			),
			new ValueItem(
				self::DONE_VALUE,
				Loc::getMessage('CRM_BADGE_BIZPROC_WORKFLOW_STATUS_DONE') ?? '',
				ValueItemOptions::TEXT_COLOR_SUCCESS,
				ValueItemOptions::BG_COLOR_SUCCESS,
			),
		];
	}

	public function getFieldName(): string
	{
		return Loc::getMessage('CRM_BADGE_BIZPROC_WORKFLOW_STATUS') ?? '';
	}

	public function getType(): string
	{
		return 'workflow_status';
	}
}
