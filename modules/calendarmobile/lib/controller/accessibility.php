<?php

namespace Bitrix\CalendarMobile\Controller;

use Bitrix\Calendar\Integration\Bitrix24Manager;
use Bitrix\Calendar\Rooms\AccessibilityManager;
use Bitrix\Calendar\Util;
use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Type\DateTime;

class Accessibility extends Controller
{
	/**
	 * @return array[]
	 */
	public function configureActions()
	{
		return [
			'get' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
			'getLocation' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
		];
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function getAction(): array
	{
		$request = $this->getRequest();

		$userIds = $request->getPost('userIds');
		$fromTs = $request->getPost('timestampFrom') / 1000;
		$toTs = $request->getPost('timestampTo') / 1000;

		$isPlannerFeatureEnabled = Bitrix24Manager::isPlannerFeatureEnabled();

		$result = [];
		if (!empty($userIds))
		{
			if ($isPlannerFeatureEnabled)
			{
				$result = (new \Bitrix\Calendar\Core\Managers\Accessibility())
					->getAccessibilityTs($userIds, $fromTs, $toTs)
				;
			}
			else
			{
				foreach ($userIds as $userId)
				{
					$result[$userId] = [];
				}
			}
		}

		return $result;
	}

	/**
	 * @return array
	 */
	public function getLocationAction(): array
	{
		$result = [];

		$request = $this->getRequest();
		$locationIds = $request->getPost('locationIds');
		$fromTs = $request->getPost('timestampFrom') / 1000;
		$toTs = $request->getPost('timestampTo') / 1000;

		$dateFrom = \CCalendar::Date($fromTs);
		$dateTo = \CCalendar::Date($toTs);

		if (empty($locationIds))
		{
			return [];
		}

		$locationAccessibility = AccessibilityManager::getRoomAccessibility(
			$locationIds,
			$dateFrom,
			$dateTo
		);

		foreach ($locationAccessibility as $locationEvent)
		{
			$locationId = (int)$locationEvent['SECTION_ID'];
			$isFullDay = $locationEvent['DT_SKIP_TIME'] === 'Y';

			if ($isFullDay)
			{
				$dateFrom = \CCalendar::Timestamp($locationEvent['DATE_FROM']);
				$dateTo = \CCalendar::Timestamp($locationEvent['DATE_TO']) + \CCalendar::GetDayLen();
			}
			else
			{
				$dateFrom = Util::getDateTimestampUtc(new DateTime($locationEvent['DATE_FROM']), $locationEvent['TZ_FROM']);
				$dateTo = Util::getDateTimestampUtc(new DateTime($locationEvent['DATE_TO']), $locationEvent['TZ_TO']);
			}

			$result[$locationId] ??= [];
			$result[$locationId][] = [
				'from' => $dateFrom,
				'to' => $dateTo,
				'isFullDay' => $isFullDay,
				'id' => (int)$locationEvent['ID'],
				'parentId' => (int)$locationEvent['PARENT_ID'],
			];
		}

		return $result;
	}
}
