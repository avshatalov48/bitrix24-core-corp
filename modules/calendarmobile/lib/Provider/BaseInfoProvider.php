<?php

namespace Bitrix\CalendarMobile\Provider;

use Bitrix\Calendar\Core\Event\Tools\Dictionary;
use Bitrix\Calendar\Integration\Bitrix24\FeatureDictionary;
use Bitrix\Calendar\Integration\Bitrix24Manager;
use Bitrix\Calendar\Internals\Counter;
use Bitrix\Calendar\Internals\Counter\CounterDictionary;
use Bitrix\Calendar\Ui\CalendarFilter;
use Bitrix\Calendar\UserSettings;
use Bitrix\Calendar\Util;
use Bitrix\CalendarMobile\Dto\Sharing;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use Bitrix\Mobile\Dto\InvalidDtoException;
use Bitrix\Mobile\Provider\UserRepository;
use Bitrix\Socialnetwork\Collab\Provider\CollabProvider;
use Bitrix\Socialnetwork\Collab\Provider\CollabQuery;
use Bitrix\Socialnetwork\Integration\Im\Chat\Workgroup;

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/calendar/install/components/bitrix/calendar.grid/component.php");

final class BaseInfoProvider
{
	public function __construct(
		private readonly int $userId,
		private readonly int $ownerId,
		private readonly string $calType,
	)
	{
	}

	/**
	 * @return Result
	 * @throws ArgumentException
	 * @throws InvalidDtoException
	 * @throws LoaderException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public function getBaseInfoConfig(): Result
	{
		$result = new Result();

		$permission = \CCalendar::GetPermissions([
			'type' => $this->calType,
			'ownerId' => $this->ownerId,
			'userId' => $this->userId,
		]);

		if (!$permission['view'])
		{
			return $result->addError(new Error(Loc::getMessage('EC_CALENDAR_NOT_PERMISSIONS_TO_VIEW_GRID_TITLE')));
		}

		if (!$this->checkPermissions())
		{
			return $result->addError(new Error(Loc::getMessage('EC_IBLOCK_ACCESS_DENIED')));
		}

		$collabs = $this->getCollabs();
		$sections = $this->getSectionInfo();
		$collabSections = $this->getCollabSections($collabs);

		return $result->setData([
			'readOnly' => $this->isReadOnly(
				$permission,
				$this->isCollabContext() ? $collabSections : $sections,
			),
			'sectionInfo' => $sections,
			'locationInfo' => $this->getLocationInfo(),
			'categoryInfo' => $this->getCategoriesInfo(),
			'sharingInfo' => $this->getSharingInfo(),
			'collabInfo' => array_values($collabs),
			'collabSectionInfo' => $collabSections,
			'counters' => $this->getCounters(),
			'syncInfo' => $this->getSyncInfo(),
			'filterPresets' => CalendarFilter::getPresets($this->calType),
			'settings' => $this->getSettings(),
			'user' => $this->getUserInfo(),
			'ahaMoments' => [
				'syncCalendar' => false,
				'syncError' => false,
			],
		]);
	}

	/**
	 * @return bool
	 * @throws LoaderException
	 */
	public function checkPermissions(): bool
	{
		$isExternalUser = Loader::includeModule('intranet') && !\Bitrix\Intranet\Util::isIntranetUser();
		$isCollaber = Util::isCollabUser($this->userId);

		if (
			$isExternalUser
			&& !in_array(
				$this->calType,
				[Dictionary::CALENDAR_TYPE['user'], Dictionary::CALENDAR_TYPE['group']],
				true
			)
		)
		{
			return false;
		}

		if ($this->calType === Dictionary::CALENDAR_TYPE['user'] && $isExternalUser && !$isCollaber)
		{
			return false;
		}

		if ($this->calType === Dictionary::CALENDAR_TYPE['user'] && $isCollaber && $this->userId !== $this->ownerId)
		{
			return false;
		}

		if ($this->calType === Dictionary::CALENDAR_TYPE['group'] && !$this->checkGroupPermissions())
		{
			return false;
		}

		return true;
	}

	/**
	 * @return bool
	 * @throws LoaderException
	 */
	public function checkGroupPermissions(): bool
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return false;
		}

		$featurePerms = \CSocNetFeaturesPerms::CurrentUserCanPerformOperation(
			SONET_ENTITY_GROUP,
			[$this->ownerId],
			'calendar',
			'view_all'
		);

		$canViewGroup = is_array($featurePerms) && isset($featurePerms[$this->ownerId]) && $featurePerms[$this->ownerId];

		if (!$canViewGroup)
		{
			$featurePerms = \CSocNetFeaturesPerms::CurrentUserCanPerformOperation(
				SONET_ENTITY_GROUP,
				[$this->ownerId],
				'calendar',
				'view'
			);
			$canViewGroup = is_array($featurePerms) && isset($featurePerms[$this->ownerId]) && $featurePerms[$this->ownerId];
		}

		return $canViewGroup;
	}

	/**
	 * @return array
	 */
	public function getSectionInfo(): array
	{
		$sections = \CCalendar::getSectionList([
			'CAL_TYPE' => $this->calType,
			'OWNER_ID' => $this->ownerId,
			'checkPermissions' => true,
			'getPermissions' => true,
		]);

		if (empty($sections))
		{
			$sections[] = \CCalendarSect::createDefault([
				'type' => $this->calType,
				'ownerId' => $this->ownerId,
			]);
		}

		return $sections;
	}

	/**
	 * @return array
	 */
	public function getBaseSettings(): array
	{
		return [
			'firstWeekday' => $this->getFirstWeekDay(),
			'meetSectionId' => \CCalendar::GetMeetingSection($this->userId),
			'pathToUserCalendar' => \Bitrix\Calendar\Util::getPathToCalendar($this->ownerId, $this->calType),
			'userTimezoneName' => \CCalendar::GetUserTimezoneName($this->userId),
		];
	}

	/**
	 * @return bool[]
	 */
	public function getUserSettings(): array
	{
		$userSettings = UserSettings::get();

		return [
			'showDeclined' => isset($userSettings['showDeclined']) && $userSettings['showDeclined'],
			'showWeekNumbers' => isset($userSettings['showWeekNumbers']) && $userSettings['showWeekNumbers'] === 'Y',
			'denyBusyInvitation' => isset($userSettings['denyBusyInvitation']) && $userSettings['denyBusyInvitation'],
		];
	}

	/**
	 * @return array
	 */
	public function getCalendarSettings(): array
	{
		$calendarSettings = \CCalendar::GetSettings();

		return [
			'workTimeStart' => $calendarSettings['work_time_start'],
			'workTimeEnd' => $calendarSettings['work_time_end'],
			'weekHolidays' => $this->getWeekHolidays($calendarSettings['week_holidays']),
			'yearHolidays' => $this->getYearHolidays($calendarSettings['year_holidays']),
			'userTimezoneName' => \CCalendar::GetUserTimezoneName($this->userId),
		];
	}

	/**
	 * @return Sharing|null
	 * @throws ArgumentException
	 * @throws InvalidDtoException
	 * @throws LoaderException
	 * @throws SystemException
	 */
	private function getSharingInfo(): ?\Bitrix\CalendarMobile\Dto\Sharing
	{
		// TODO: handle group sharing later maybe ?
		if (Loader::includeModule('intranet') && !\Bitrix\Intranet\Util::isIntranetUser())
		{
			return null;
		}

		if ($this->calType !== Dictionary::CALENDAR_TYPE['user'])
		{
			return null;
		}

		$sharing = new \Bitrix\Calendar\Sharing\Sharing($this->userId);

		return \Bitrix\CalendarMobile\Dto\Sharing::make([
			'isEnabled' => !empty($sharing->getActiveLinkShortUrl()),
			'isRestriction' => !Bitrix24Manager::isFeatureEnabled(FeatureDictionary::CALENDAR_SHARING),
			'isPromo' => Bitrix24Manager::isPromoFeatureEnabled(FeatureDictionary::CALENDAR_SHARING),
			'shortUrl' => $sharing->getActiveLinkShortUrl(),
			'userInfo' => $sharing->getUserInfo(),
			'settings' => $sharing->getLinkSettings(),
			'options' => $sharing->getOptions(),
		]);
	}

	/**
	 * @param array $collabGroup
	 * @return array
	 */
	private function getCollabSections(array $collabGroup): array
	{
		if ($this->calType !== Dictionary::CALENDAR_TYPE['user'])
		{
			return [];
		}

		$collabIds = array_keys($collabGroup);

		if (empty($collabIds))
		{
			return [];
		}

		return \CCalendar::GetSectionList([
			'CAL_TYPE' => Dictionary::CALENDAR_TYPE['group'],
			'OWNER_ID' => $collabIds,
			'checkPermissions' => true,
			'getPermissions' => true,
		]);
	}


	/**
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function getCollabs(): array
	{
		$result = [];

		if (!Loader::includeModule('socialnetwork'))
		{
			return $result;
		}

		$collabService = new CollabProvider();
		$collabQuery = (new CollabQuery())->setSelect(['ID', 'NAME']);

		$collabs = $collabService->getListByUserId($this->userId, $collabQuery);

		if ($collabs->isEmpty())
		{
			return $result;
		}

		$collabsChatData = Workgroup::getChatData([
			'group_id' => $collabs->getIdList(),
		]);

		foreach ($collabs as $collab)
		{
			$collabId = $collab->getId();

			$result[$collabId] = [
				'ID' => $collabId,
				'NAME' => $collab->getName(),
				'CHAT_ID' => (int)($collabsChatData[$collab->getId()] ?? null),
			];
		}

		return $result;
	}

	/**
	 * @return array|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getLocationInfo(): ?array
	{
		return \Bitrix\Calendar\Rooms\Manager::getRoomsList();
	}

	/**
	 * @return array|null
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getCategoriesInfo(): ?array
	{
		return \Bitrix\Calendar\Rooms\Categories\Manager::getCategoryList();
	}

	/**
	 * @return array
	 */
	private function getSyncInfo(): array
	{
		if ($this->ownerId === $this->userId && $this->calType === Dictionary::CALENDAR_TYPE['user'])
		{
			$calculateTimestamp = \CCalendarSync::getTimestampWithUserOffset($this->userId);
			$syncInfo = \CCalendarSync::getNewSyncItemsInfo($this->userId, $calculateTimestamp);

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

		return [];
	}


	/**
	 * @return array
	 */
	private function getUserInfo(): array
	{
		if (empty($this->userId))
		{
			return [];
		}

		return UserRepository::getByIds([$this->userId]);
	}


	/**
	 * @return array
	 */
	private function getSettings(): array
	{
		return [
			...$this->getBaseSettings(),
			...$this->getCalendarSettings(),
			...$this->getUserSettings(),
		];
	}

	/**
	 * @return int
	 */
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

	/**
	 * @param $weekHolidays
	 * @return array
	 */
	private function getWeekHolidays($weekHolidays): array
	{
		$result = [];

		foreach ($weekHolidays as $weekHoliday)
		{
			$result[] = \CCalendar::IndByWeekDay($weekHoliday);
		}

		return $result;
	}

	/**
	 * @param $yearHolidays
	 * @return array
	 */
	private function getYearHolidays($yearHolidays): array
	{
		return explode(',', $yearHolidays);
	}


	/**
	 * @return array
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	private function getCounters(): array
	{
		if ($this->ownerId === $this->userId && $this->calType === Dictionary::CALENDAR_TYPE['user'])
		{
			return [
				CounterDictionary::COUNTER_TOTAL => Counter::getInstance($this->userId)->get(CounterDictionary::COUNTER_TOTAL),
				CounterDictionary::COUNTER_INVITES => Counter::getInstance($this->userId)->get(CounterDictionary::COUNTER_INVITES),
				CounterDictionary::COUNTER_SYNC_ERRORS => Counter::getInstance($this->userId)->get(CounterDictionary::COUNTER_SYNC_ERRORS),
			];
		}
		if ($this->calType === Dictionary::CALENDAR_TYPE['group'])
		{
			return [
				CounterDictionary::COUNTER_GROUP_INVITES => Counter::getInstance($this->userId)->get(CounterDictionary::COUNTER_GROUP_INVITES, $this->ownerId),
			];
		}

		return [];
	}

	/**
	 * @param array $permission
	 * @param array $sections
	 *
	 * @return bool
	 */
	private function isReadOnly(array $permission, array $sections): bool
	{
		$readOnly = !$permission['edit'] && !$permission['section_edit'];

		if ($this->calType === Dictionary::CALENDAR_TYPE['user'] && $this->userId !== $this->ownerId)
		{
			$readOnly = true;
		}

		$groupOrUser = $this->calType === Dictionary::CALENDAR_TYPE['user']
			|| $this->calType === Dictionary::CALENDAR_TYPE['group']
		;
		$noEditAccessedCalendars = $groupOrUser;

		foreach ($sections as $section)
		{
			if ($noEditAccessedCalendars && $section['PERM']['edit'])
			{
				$noEditAccessedCalendars = false;
			}

			if ($readOnly && ($section['PERM']['edit'] || $section['PERM']['edit_section']))
			{
				$readOnly = false;
			}
		}

		if ($groupOrUser && $noEditAccessedCalendars)
		{
			$readOnly = true;
		}

		return $readOnly;
	}

	/**
	 * @return bool
	 */
	private function isCollabContext(): bool
	{
		return $this->calType === Dictionary::CALENDAR_TYPE['user'] && Util::isCollabUser($this->userId);
	}
}
