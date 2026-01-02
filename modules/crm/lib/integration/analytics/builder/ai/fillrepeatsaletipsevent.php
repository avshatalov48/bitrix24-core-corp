<?php

namespace Bitrix\Crm\Integration\Analytics\Builder\AI;

use Bitrix\Crm\Integration\Analytics\Dictionary;

final class FillRepeatSaleTipsEvent extends AIBaseEvent
{
	protected function getEvent(): string
	{
		return Dictionary::EVENT_FILL_REPEAT_SALE_TIPS;
	}
}
