<?php

namespace Bitrix\Crm\Integration\Analytics\Builder\AI;

use Bitrix\Crm\Integration\Analytics\Dictionary;

final class CallScoring extends AIBaseEvent
{
	protected function getEvent(): string
	{
		return Dictionary::EVENT_CALL_SCORING;
	}
}
