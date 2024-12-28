<?php

namespace Bitrix\CalendarMobile\Provider;

use Bitrix\Calendar\Core\Event\Tools\Dictionary;
use Bitrix\Calendar\Integration\Bitrix24\FeatureDictionary;
use Bitrix\Calendar\Integration\Bitrix24Manager;
use Bitrix\Calendar\Integration\SocialNetwork\Collab\UserCollabs;
use Bitrix\Calendar\Rooms;
use Bitrix\Calendar\UserSettings;
use Bitrix\Calendar\Util;
use Bitrix\Intranet\Settings\Tools\ToolsManager;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Mobile\Provider\UserRepository;

final class EditFormProvider
{
	public function __construct(
		private readonly int $userId,
		private readonly int $ownerId,
		private readonly string $calType,
		private readonly array $userIdsToRequest = [],
	)
	{
	}

	/**
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getEditFormConfig(): Result
	{
		$result = new Result();

		if (!$this->checkToolAvailability())
		{
			return $result->addError(new Error('Tool not available'));
		}

		$baseInfoProvider = new BaseInfoProvider($this->userId, $this->ownerId, $this->calType);
		if (!$baseInfoProvider->checkPermissions())
		{
			return $result->addError(new Error('Permission denied'));
		}

		$locationFeatureEnabled = Bitrix24Manager::isFeatureEnabled(FeatureDictionary::CALENDAR_LOCATION);
		$locationList = $locationFeatureEnabled ? Rooms\Manager::getRoomsList() : [];
		$categoryList = $locationFeatureEnabled ? Rooms\Categories\Manager::getCategoryList() : [];
		$users = !empty($this->userIdsToRequest) ? UserRepository::getByIds($this->userIdsToRequest) : [];

		return $result->setData([
			'user' => UserRepository::getByIds([$this->userId]),
			'users' => $users,
			'sections' => $this->getSections(),
			'settings' => $baseInfoProvider->getCalendarSettings(),
			'meetSection' => $this->getMeetingSection(),
			'firstWeekday' => $this->getFirstWeekday(),
			'locationList' => $locationList,
			'categoryList' => $categoryList,
		]);
	}

	/**
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	private function checkToolAvailability(): bool
	{
		return !(Loader::includeModule('intranet')
			&& !ToolsManager::getInstance()->checkAvailabilityByToolId('calendar'))
		;
	}

	/**
	 * @return array
	 */
	private function getSections(): array
	{
		$sections = [];
		$userCollabIds = [];
		$isCollabUser = Util::isCollabUser($this->userId);

		if ($isCollabUser && $this->calType === Dictionary::CALENDAR_TYPE['user'])
		{
			$userCollabIds = UserCollabs::getInstance()->getIds($this->userId);

			if (empty($userCollabIds))
			{
				return $sections;
			}

			$sectionList = \CCalendar::getSectionList([
				'CAL_TYPE' => Dictionary::CALENDAR_TYPE['group'],
				'OWNER_ID' => $userCollabIds,
				'ACTIVE' => 'Y',
				'checkPermissions' => true,
				'getPermissions' => true
			]);
		}
		else
		{
			$sectionList = \CCalendar::getSectionList([
				'CAL_TYPE' => $this->calType,
				'OWNER_ID' => $this->ownerId,
				'ACTIVE' => 'Y',
				'checkPermissions' => true,
				'getPermissions' => true
			]);
		}

		foreach ($sectionList as $section)
		{
			if ($section['PERM']['edit'] || $section['PERM']['add'])
			{
				$sections[] = $section;
			}
		}

		if (
			$isCollabUser
			&& $this->calType === Dictionary::CALENDAR_TYPE['user']
			&& empty($sections)
			&& empty($sectionList)
		)
		{
			$collabId = current($userCollabIds);

			$sections[] = \CCalendarSect::createDefault([
				'type' => Dictionary::CALENDAR_TYPE['group'],
				'ownerId' => $collabId,
			]);
		}
		else if (
			empty($sections)
			&& empty($sectionList) // Have no rights to create events in this calendar type
		)
		{
			$sections[] = \CCalendarSect::createDefault([
				'type' => $this->calType,
				'ownerId' => $this->ownerId,
			]);
		}

		return $sections;
	}

	private function getMeetingSection(): int
	{
		$result = null;

		if ($this->calType === Dictionary::CALENDAR_TYPE['user'])
		{
			$result = \CCalendar::GetMeetingSection($this->userId);
		}

		return (int)$result;
	}

	private function getFirstWeekday(): int
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
}
