<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\AI\Call;

use Bitrix\Main\Localization\Loc;

final class CallScoringResultStarted extends Base
{
	public function getType(): string
	{
		return 'CallScoringResultStarted';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_LOG_CALL_SCORING_STARTED');
	}
}
