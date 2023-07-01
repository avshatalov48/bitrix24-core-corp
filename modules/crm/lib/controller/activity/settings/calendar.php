<?php

namespace Bitrix\Crm\Controller\Activity\Settings;

use Bitrix\Crm\Controller\Base;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use CCalendar;

class Calendar extends Base
{
	public function getAccessibilityForUsersAction(int $from, int $to, int $currentEventId = 0): array
	{
		if (!Loader::includeModule('calendar'))
		{
			return [];
		}

		return [
			'entries' => [$this->getCurrentUserEntry()],
			'accessibility' => $this->getAccessibility($from, $to, $currentEventId),
		];
	}

	private function getCurrentUserEntry(): array
	{
		$user = $this->getCurrentUser();
		$userId = $user->getId();

		return [
			'type' => 'user',
			'id' => $userId,
			'name' => $user->getFormattedName(),
			'status' => '',
			'avatar' => CCalendar::GetUserAvatarSrc($userId),
		];
	}

	private function getAccessibility(int $from, int $to, int $currentEventId): array
	{
		$result = [];

		$fromDate = DateTime::createFromTimestamp($from)->toUserTime();
		$toDate = DateTime::createFromTimestamp($to)->toUserTime();

		$accessibility = CCalendar::GetAccessibilityForUsers([
			'users' => [$this->getCurrentUser()->getId()],
			'from' => $fromDate,
			'to' => $toDate,
			'curEventId' => $currentEventId,
			'getFromHR' => true,
		]);

		foreach ($accessibility as $userId => $entries)
		{
			foreach ($entries as $entry)
			{
				if (isset($entry['DT_FROM']) && !isset($entry['DATE_FROM']))
				{
					$result[$userId][] = [
						'id' => $entry['ID'],
						'dateFrom' => $entry['DT_FROM'],
						'dateTo' => $entry['DT_TO'],
						'type' => $entry['FROM_HR'] ? 'hr' : 'event',
						'title' => $entry['NAME'],
					];
					continue;
				}

				$fromTs = CCalendar::Timestamp($entry['DATE_FROM']);
				$toTs = CCalendar::Timestamp($entry['DATE_TO']);
				if ($entry['DT_SKIP_TIME'] !== 'Y')
				{
					$fromTs -= $entry['~USER_OFFSET_FROM'];
					$toTs -= $entry['~USER_OFFSET_TO'];
				}

				$result[$userId][] = [
					'id' => $entry['ID'],
					'dateFrom' => CCalendar::Date($fromTs, $entry['DT_SKIP_TIME'] !== 'Y'),
					'dateTo' => CCalendar::Date($toTs, $entry['DT_SKIP_TIME'] !== 'Y'),
					'type' => $entry['FROM_HR'] ? 'hr' : 'event',
					'title' => $entry['NAME'],
				];
			}
		}

		return $result;
	}
}
