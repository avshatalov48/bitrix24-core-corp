<?php

namespace Bitrix\Crm\Badge\Type;

use Bitrix\Crm\Badge\Badge;
use Bitrix\Crm\Badge\ValueItem;
use Bitrix\Crm\Badge\ValueItemOptions;
use Bitrix\Main\Localization\Loc;

final class AiCallScoringStatus extends Badge
{
	protected const TYPE = 'ai_call_scoring_status';

	public const FAILED_VALUE = 'failed';

	public function getFieldName(): string
	{
		return Loc::getMessage('CRM_COMMON_COPILOT');
	}

	public function getValuesMap(): array
	{
		return [
			new ValueItem(
				self::FAILED_VALUE,
				Loc::getMessage('CRM_BADGE_AI_CALL_SCORING_FAILED_VALUE'),
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
