<?php

namespace Bitrix\Crm\Activity\ToDo\CalendarSettings;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;

final class CalendarSettingsProvider
{
	public function fetchForJsComponent(): array
	{
		$timezoneName = '';
		if (Loader::includeModule('calendar'))
		{
			$timezoneName = \CCalendar::GetUserTimezoneName(CurrentUser::get()->getId());
		}

		$from = (new DateTime())->add('+3 days')->add('+1 hour');

		return [
			'timezoneName' => $timezoneName,
			'ownerId' => CurrentUser::get()->getId(),
			'from' => $from->setTime((int)$from->format('H'), 0, 0)->getTimestamp(),
		];
	}
}
