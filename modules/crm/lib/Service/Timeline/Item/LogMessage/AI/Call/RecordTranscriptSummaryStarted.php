<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\AI\Call;

use Bitrix\Main\Localization\Loc;

final class RecordTranscriptSummaryStarted extends Base
{
	public function getType(): string
	{
		return 'RecordTranscriptSummaryStarted';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_LOG_TRANSCRIPT_SUMMARY_STARTED');
	}
}
