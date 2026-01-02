<?php

namespace Bitrix\Crm\Integration\Analytics\Builder\Activity;

use Bitrix\Crm\Activity\Analytics\Dictionary;

final class EditActivityEvent extends ActivityBaseEvent
{
	protected function getEvent(): string
	{
		return Dictionary::EDIT_EVENT;
	}
}
