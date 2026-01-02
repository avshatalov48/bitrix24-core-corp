<?php

namespace Bitrix\Crm\Integration\AI\Operation\Payload\Payload;

final class ScoringCriteriaExtraction extends AbstractPayload
{
	public function getPayloadCode(): string
	{
		return 'scoring_criteria_extraction';
	}
}
