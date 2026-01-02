<?php

namespace Bitrix\Crm\Integration\Analytics\Builder\Activity;

use Bitrix\Crm\Activity\Analytics\Dictionary;

final class CompleteActivityEvent extends ActivityBaseEvent
{
	protected function getEvent(): string
	{
		return Dictionary::COMPLETE_EVENT;
	}
}
