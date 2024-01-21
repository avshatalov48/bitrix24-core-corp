<?php

namespace Bitrix\CalendarMobile\Controller;

use Bitrix\Calendar\Ui\CalendarFilter;
use Bitrix\CalendarMobile\AhaMoments\Factory;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Loader;

Loader::requireModule('calendar');

class Event extends Controller
{
	public function loadMainAction(): array
	{
		$userSettings = \Bitrix\Calendar\UserSettings::get();
		$syncInfo = $this->getSyncInfo();
		$isSyncHasError = $this->isSyncHasError($syncInfo);

		return [
			'sharingInfo' => $this->getSharingInfo(),
			'sectionInfo' => $this->getSectionInfo(),
			'locationInfo' => $this->getLocationInfo(),
			'syncInfo' => $syncInfo,
			'ahaMoments' => [
				'syncCalendar' => Factory::getInstance()->getAhaInstance('SyncCalendar')?->canShow(),
				'syncError' => $isSyncHasError && Factory::getInstance()->getAhaInstance('SyncError')?->canShow(),
			],
			'filterPresets' => CalendarFilter::getPresets('user'),
			'settings' => [
				'firstWeekday' => $this->getFirstWeekDay(),
				'showDeclined' => isset($userSettings['showDeclined']) && $userSettings['showDeclined'],
				'showWeekNumbers' => isset($userSettings['showWeekNumbers']) && $userSettings['showWeekNumbers'] === 'Y',
			],
		];
	}

	public function getListAction()
	{
		$request = $this->getRequest();
		$yearFrom = (int)$request->getPost('yearFrom');
		$yearTo = (int)$request->getPost('yearTo');
		$monthFrom = (int)$request->getPost('monthFrom');
		$monthTo = (int)$request->getPost('monthTo');
		$limits = \CCalendarEvent::getLimitDates($yearFrom, $monthFrom, $yearTo, $monthTo);

		$sectionIdList = is_array($request->getPost('sectionIdList'))
			? array_map(static fn($section) => (int)$section, $request->getPost('sectionIdList'))
			: []
		;

		$events = [];
		if (!empty($sectionIdList))
		{
			$events = $this->getEvents($sectionIdList, $limits);
		}

		return [
			'events' => $events,
		];
	}

	public function getFilteredListAction(): array
	{
		$request = $this->getRequest();
		$search = (string)$request->getPost('search');
		$preset = $request->getPost('preset');
		
		$userId = \CCalendar::GetUserId();
		$ownerId = $userId;
		$calendarType = 'user';

		$sectionList = \CCalendar::getSectionList([
			'CAL_TYPE' => $calendarType,
			'OWNER_ID' => $ownerId,
			'checkPermissions' => true,
			'getPermissions' => true,
		]);
		$sectionIds = [];
		foreach ($sectionList as $section)
		{
			$sectionIds[] = (int)$section['ID'];
		}
		
		$presetId = null;
		$fields = [
			'SECTION_ID' => $sectionIds,
		];
		
		if (!empty($preset) && is_array($preset))
		{
			if (!empty($preset['id']))
			{
				$presetId = $preset['id'];
			}
			
			if (!empty($preset['fields']))
			{
				$fields = array_merge($fields, $preset['fields']);
			}
		}

		$searchFields = [
			'search' => $search,
			'presetId' => $presetId,
			'fields' => $fields
		];

		$searchResult = CalendarFilter::getFilterUserData($calendarType, $userId, $ownerId, $searchFields);

		return [
			'events' => $searchResult['entries'],
		];
	}

	public function setAhaViewedAction(string $name): void
	{
		Factory::getInstance()->getAhaInstance($name)?->setViewed();
	}

	public function getSectionListAction(): array
	{
		return [
			'sections' => $this->getSectionInfo(),
		];
	}

	private function getSharingInfo(): ?\Bitrix\CalendarMobile\Dto\Sharing
	{
		$sharing = new \Bitrix\Calendar\Sharing\Sharing(\CCalendar::GetCurUserId());
		
		return new \Bitrix\CalendarMobile\Dto\Sharing([
			'isEnabled' => !empty($sharing->getActiveLinkShortUrl()),
			'isRestriction' => !\Bitrix\Calendar\Integration\Bitrix24Manager::isFeatureEnabled('calendar_sharing'),
			'shortUrl' =>  $sharing->getActiveLinkShortUrl(),
			'settings' => $sharing->getLinkSettings()
		]);
	}

	private function getSectionInfo(): array
	{
		return \CCalendar::GetSectionList([
			'CAL_TYPE' => 'user',
			'OWNER_ID' => \CCalendar::GetCurUserId(),
			'checkPermissions' => true,
			'getPermissions' => true,
		]);
	}

	private function getFirstWeekDay(): int
	{
		$weekDayIndex = [
			'SU' => 1,
			'MO' => 2,
			'TU' => 3,
			'WE' => 4,
			'TH' => 5,
			'FR' => 6,
			'SA' => 7,
		];

		$weekDay = \CCalendar::GetWeekStart();

		return $weekDayIndex[$weekDay];
	}

	private function getLocationInfo(): ?array
	{
		return \Bitrix\Calendar\Rooms\Manager::getRoomsList();
	}

	private function getEvents(array $sections, array $limits)
	{
		return \CCalendarEvent::GetList([
			'arFilter' => [
				'SECTION' => $sections,
				'FROM_LIMIT' => $limits['from'],
				'TO_LIMIT' => $limits['to'],
			],
			'parseRecursion' => true,
			'fetchAttendees' => true,
			'userId' => \CCalendar::GetCurUserId(),
			'setDefaultLimit' => false,
		]);
	}

	private function getSyncInfo(): array
	{
		$userId = \CCalendar::GetCurUserId();
		$calculateTimestamp = \CCalendarSync::getTimestampWithUserOffset($userId);
		$syncInfo = \CCalendarSync::getNewSyncItemsInfo($userId, $calculateTimestamp);

		$defaultSyncData = static function($name){
			return [
				'type' => $name,
				'active' => false,
				'connected' => false,
			];
		};

		return [
			'google' => !empty($syncInfo['google']) ? $syncInfo['google'] : $defaultSyncData('google'),
			'office365' => !empty($syncInfo['office365']) ? $syncInfo['office365'] : $defaultSyncData('office365'),
			'icloud' => !empty($syncInfo['icloud']) ? $syncInfo['icloud'] : $defaultSyncData('icloud'),
		];
	}

	private function isSyncHasError($syncInfo): bool
	{
		foreach ($syncInfo as $item)
		{
			if ($item['connected'] === true && $item['status'] === false)
			{
				return true;
			}
		}

		return false;
	}
}