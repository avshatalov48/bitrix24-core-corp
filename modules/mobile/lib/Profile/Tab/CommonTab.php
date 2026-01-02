<?php

namespace Bitrix\Mobile\Profile\Tab;

use Bitrix\Intranet\Enum\InvitationStatus;
use Bitrix\Intranet\Public\Provider\User\UserProfileProvider;
use Bitrix\Intranet\Service\ServiceContainer;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\Profile\Enum\TabContextType;
use Bitrix\Mobile\Profile\Enum\TabType;
use Bitrix\Mobile\Profile\Enum\UserStatus;
use Bitrix\Mobile\Profile\Provider\GratitudeProvider;
use Bitrix\Mobile\Profile\Provider\ProfileProvider;
use Bitrix\Mobile\Profile\Provider\TagProvider;
use Bitrix\Mobile\Provider\CommonUserDto;
use Bitrix\Mobile\Provider\UserRepository;
use Bitrix\IntranetMobile\Provider\DepartmentProvider;
use Bitrix\Tasks\Internals\Effective;
use Bitrix\Tasks\Util\User as TasksUser;
use Bitrix\IntranetMobile\Provider\InviteProvider;

class CommonTab extends BaseProfileTab
{
	/**
	 * @return TabType
	 */
	public function getType(): TabType
	{
		return TabType::COMMON;
	}

	/**
	 * @return TabContextType
	 */
	public function getContextType(): TabContextType
	{
		return TabContextType::WIDGET;
	}

	/**
	 * @return bool
	 */
	public function isAvailable(): bool
	{
		return Loader::includeModule('intranet');
	}

	/**
	 * @return string
	 */
	public function getTitle(): string
	{
		return Loc::getMessage('PROFILE_TAB_COMMON_TITLE');
	}

	/**
	 * @return array
	 */
	public function getParams(): array
	{
		return [
			'ownerId' => $this->ownerId,
			'canUpdate' => (new ProfileProvider($this->viewerId, $this->ownerId))->canUpdate(),
			'data' => $this->getData(),
		];
	}

	/**
	 * @return array
	 * @throws LoaderException
	 */
	public function getData(): array
	{
		$userOwner = UserRepository::getByIds([$this->ownerId]);

		if (empty($userOwner))
		{
			return [];
		}

		$owner = $userOwner[0];
		$statusData = $this->getOwnerStatusData($owner);
		$tagsData = $this->getTagsData();
		$users = [$owner, ...UserRepository::getByIds($tagsData['userIds'])];

		return [
			'owner' => $owner,
			'users' => $users,
			'statusData' => $statusData,
			'gratitude' => $this->getGratitudeData(),
			'tags' => $tagsData['tags'],
			'departments' => $this->getDepartmentData(),
			'efficiency' => $this->getEfficiencyData(),
			'commonFields' => $this->getCommonFields(),
		];
	}

	private function getCommonFields(): array
	{
		$sectionArray = [];
		$sectionCollection = UserProfileProvider::createByDefault()
			->getByUserId($this->ownerId)
			->fieldSectionCollection;

		foreach ($sectionCollection as $section)
		{
			$sectionData = [
				'id' => $section->getId(),
				'title' => $section->title,
				'isEditable' => $section->isEditable,
				'isRemovable' => $section->isRemovable,
				'fields' => [],
			];

			foreach ($section->userFieldCollection as $userField)
			{
				$field = $userField->toArray();
				$sectionData['fields'][] = $field;
			}

			$sectionArray[] = $sectionData;
		}

		return $sectionArray;
	}

	/**
	 * @param \Bitrix\Mobile\Provider\CommonUserDto $owner
	 * @return array
	 */
	private function getOwnerStatusData(CommonUserDto $owner): array
	{
		$result = [
			'onVacationDateTo' => '',
			'lastSeenText' => '',
		];

		$isFired = $this->isOwnerFired();
		if ($isFired)
		{
			$result['status'] = UserStatus::FIRED;

			return $result;
		}

		$onVacationDateTo = $this->getMaxDateToFromAbsences($this->getOwnerAbsences());
		if (!empty($onVacationDateTo))
		{
			$result['status'] = UserStatus::ON_VACATION;
			$result['onVacationDateTo'] = $onVacationDateTo;

			return $result;
		}

		$onlineStatus = $this->getOnlineStatus($owner->lastActivityDate);
		$result['lastSeenText'] = $onlineStatus['LAST_SEEN_TEXT'];
		$result['lastSeenDate'] = $onlineStatus['LAST_SEEN'];

		if ($onlineStatus['STATUS'] === 'dnd')
		{
			$result['status'] = UserStatus::DND;
			$result['GMTString'] = $this->getGMTString($owner->timezone);

			return $result;
		}

		if ($onlineStatus['IS_ONLINE'])
		{
			$result['status'] = UserStatus::ONLINE;
			$result['GMTString'] = $this->getGMTString($owner->timezone);

			return $result;
		}

		$result['status'] = UserStatus::OFFLINE;

		return $result;
	}

	/**
	 * @param \DateTimeZone $timezone
	 * @return string
	 * @throws \DateMalformedStringException
	 */
	private function getGMTString(\DateTimeZone $timezone): string
	{
		$dateTime = new \DateTime('now', $timezone);
		$offsetInSeconds = $timezone->getOffset($dateTime);
		$hours = intdiv($offsetInSeconds, 3600);
		$minutes = abs($offsetInSeconds % 3600) / 60;

		$gmt = sprintf("GMT%+d:%02d", $hours, $minutes);
		$time = $dateTime->format('H:i');

		return "{$gmt} ({$time})";
	}

	/**
	 * @param ?string $lastActivityDate
	 * @return array
	 */
	private function getOnlineStatus(?string $lastActivityDate): array
	{
		return \CUser::GetOnlineStatus(
			$this->ownerId,
			MakeTimeStamp(empty($lastActivityDate) ? null : $lastActivityDate, "DD.MM.YYYY HH:MI:SS"),
		);
	}

	/**
	 * @param array $absenceData
	 * @return string
	 */
	private function getMaxDateToFromAbsences(array $absenceData): string
	{
		if (!empty($absenceData) && $absenceData['IS_VACATION'] === true)
		{
			return (new \DateTime())
				->setTimestamp($absenceData['DATE_TO_TS'])
				->format('d.m.Y')
			;
		}

		return '';
	}

	private function getOwnerAbsences(): array
	{
		$result = \Bitrix\Intranet\UserAbsence::isAbsentOnVacation($this->ownerId, true);
		if (empty($result) || !is_array($result))
		{
			return [];
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	private function isOwnerFired(): bool
	{
		$this->userRepository = ServiceContainer::getInstance()->userRepository();
		$intranetUser = $this->userRepository->findUsersByIds([$this->ownerId])->first();
		if (empty($intranetUser))
		{
			return false;
		}

		return $intranetUser->getInviteStatus() === InvitationStatus::FIRED;
	}

	/**
	 * @return array
	 * @throws LoaderException
	 */
	private function getGratitudeData(): array
	{
		$gratitudeProvider = new GratitudeProvider();
		$limit = 10;

		return $gratitudeProvider->getBadges($this->ownerId, $limit);
	}

	private function getTagsData(): array
	{
		$tagProvider = new TagProvider();

		return (new TagProvider())->getTagsList($this->ownerId);
	}

	private function getEfficiencyData(): ?array
	{
		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		$currentUser = TasksUser::getId();
		$isAvailable = $currentUser === $this->ownerId
			|| TasksUser::isSuper($currentUser)
			|| TasksUser::isBossRecursively($currentUser, $this->ownerId);

		if(!$isAvailable)
		{
			return null;
		}

		$datesRange = Effective::getDatesRange();
		$tasksCounters = Effective::getCountersByRange(
			dateFrom: $datesRange['FROM'],
			dateTo: $datesRange['TO'],
			userId: $this->ownerId,
		);

		return [
			'completed' => $tasksCounters['COMPLETED'],
			'violations' => $tasksCounters['VIOLATIONS'],
			'inProgress' => $tasksCounters['IN_PROGRESS'],
		];
	}

	private function getDepartmentData(): array
	{
		$result = [
			'departmentHierarchies' => [],
			'canInviteUsers' => false,
			'canUseTelephony' => false,
		];
		if (!Loader::includeModule('intranetmobile'))
		{
			return $result;
		}
		$result['departmentHierarchies'] = (new DepartmentProvider())->getUserDepartments($this->ownerId);
		$result['canInviteUsers'] = (new InviteProvider())->getInviteSettings()['canCurrentUserInvite'];
		$result['canUseTelephony'] = (
			Loader::includeModule('voximplant')
			&& \Bitrix\Voximplant\Security\Helper::canCurrentUserPerformCalls()
		);

		return $result;
	}
}
