<?php

namespace Bitrix\Crm\Badge\Type;

use Bitrix\Crm\Badge\Badge;
use Bitrix\Crm\Badge\ValueItem;
use Bitrix\Crm\Badge\ValueItemOptions;
use Bitrix\Main\Localization\Loc;

class CopilotCallAssessmentStatus extends Badge
{
	protected const TYPE = 'copilot_call_assessment_status';

	public const ERROR_VALUE = 'ERROR';

	public function getFieldName(): string
	{
		return Loc::getMessage('CRM_BADGE_COPILOT_CALL_ASSESSMENT_STATUS_FIELD_NAME');
	}

	public function getValuesMap(): array
	{
		return [
			new ValueItem(
				self::ERROR_VALUE,
				Loc::getMessage('CRM_BADGE_COPILOT_CALL_ASSESSMENT_STATUS_ERROR_VALUE'),
				ValueItemOptions::TEXT_COLOR_FAILURE,
				ValueItemOptions::BG_COLOR_FAILURE
			),
		];
	}

	public function getType(): string
	{
		return self::TYPE;
	}
}
