<?php

namespace Bitrix\Crm\Integration\Analytics\Builder\AI;

use Bitrix\Crm\Integration\Analytics\Dictionary;

final class ExtractScoringCriteriaEvent extends AIBaseEvent
{
	protected function getEvent(): string
	{
		return Dictionary::EVENT_EXTRACT_SCORING_CRITERIA;
	}
}
