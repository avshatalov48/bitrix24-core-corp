<?php

namespace Bitrix\Crm\Activity\Entity;

use Bitrix\Crm\Activity\Provider;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;

class ToDo extends BaseActivity
{
	public function isValidProviderId(string $providerId): bool
	{
		return $this->provider::getId() === Provider\ToDo\ToDo::getId() && $providerId === Provider\ToDo\ToDo::getId();
	}

	public function getProviderId(): string
	{
		return Provider\ToDo\ToDo::PROVIDER_ID;
	}

	public function getProviderTypeId(): string
	{
		return Provider\ToDo\ToDo::PROVIDER_TYPE_ID_DEFAULT;
	}

	public function save(array $options = [], $useCurrentSettings = false): Result
	{
		$this->tryAppendTags();
		return parent::save($options, $useCurrentSettings);
	}

	private function tryAppendTags(): void
	{
		$additionalFields = $this->getAdditionalFields();

		if (
			empty($additionalFields['START_TIME'])
			|| empty($additionalFields['END_TIME'])
			|| empty($additionalFields['SETTINGS']['USERS'])
		)
		{
			return;
		}

		$calendarEventId = null;
		if ($this->getCalendarEventId())
		{
			$calendarEventId = $this->getCalendarEventId();
		}
		elseif ($this->getId())
		{
			$existedActivity = \CCrmActivity::GetByID($this->getId());

			if ($existedActivity && isset($existedActivity['CALENDAR_EVENT_ID']))
			{
				$calendarEventId = $existedActivity['CALENDAR_EVENT_ID'];
			}
		}

		$userIds = $additionalFields['SETTINGS']['USERS'];
		$fromTimestampEvent = DateTime::createFromUserTime($additionalFields['START_TIME'])->getTimestamp();
		$toTimestampEvent = DateTime::createFromUserTime($additionalFields['END_TIME'])->getTimestamp();

		if ($this->hasOverlapEvent($userIds, $fromTimestampEvent, $toTimestampEvent, $calendarEventId))
		{
			$additionalFields['SETTINGS']['TAGS'] = ['OVERLAP_EVENT' => true];
			$this->setAdditionalFields($additionalFields);
		}
	}

	private function hasOverlapEvent(
		array $userIds,
		int   $fromTimestampEvent,
		int   $toTimestampEvent,
		?int $currentCalendarEventId = null
	): bool
	{
		$busyUsersIds = \Bitrix\Crm\Integration\Calendar::getBusyUsersIds(
			$userIds,
			$fromTimestampEvent,
			$toTimestampEvent,
			$currentCalendarEventId
		);

		return !empty($busyUsersIds);
	}
}
