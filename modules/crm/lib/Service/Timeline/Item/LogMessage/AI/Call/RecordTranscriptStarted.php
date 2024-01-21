<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\AI\Call;

use Bitrix\Main\Localization\Loc;

final class RecordTranscriptStarted extends Base
{
	public function getType(): string
	{
		return 'RecordTranscriptStarted';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_LOG_TRANSCRIPT_STARTED');
	}
}
