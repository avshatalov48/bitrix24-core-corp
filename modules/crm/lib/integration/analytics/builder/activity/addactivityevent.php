<?php

namespace Bitrix\Crm\Integration\Analytics\Builder\Activity;

use Bitrix\Crm\Activity\Analytics\Dictionary;

final class AddActivityEvent extends ActivityBaseEvent
{
	protected function getEvent(): string
	{
		return Dictionary::ADD_EVENT;
	}
}
