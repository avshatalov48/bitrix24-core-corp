<?php

namespace Bitrix\Crm\Activity\Provider\ToDo\Block;

use Bitrix\Crm\Activity\Provider\ToDo\OptionallyConfigurable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use CCalendarEvent;
use DateTimeZone;

final class Calendar extends Base
{
	public const TYPE_NAME = 'calendar';
	private bool $isCalendarIncluded;

	public function __construct(array $blockData = [], array $activityData = [])
	{
		parent::__construct($blockData, $activityData);

		$this->isCalendarIncluded = Loader::includeModule('calendar');
	}

	public function fetchSettings(): array
	{
		$result = [];

		$calendarEventId = $this->activityData['calendarEventId'];
		if (!$calendarEventId)
		{
			return $result;
		}

		$eventData = \Bitrix\Crm\Integration\Calendar::getEvent($calendarEventId);
		if (is_array($eventData))
		{
			$from = (new DateTime(
				$eventData['DATE_FROM'],
				null,
				new DateTimeZone($eventData['TZ_FROM'])
			))->getTimestamp();

			$to = (new DateTime(
				$eventData['DATE_TO'],
				null,
				new DateTimeZone($eventData['TZ_TO'])
			))->getTimestamp() ;

			$milliseconds = 1000;

			$result = [
				'attendeesEntityList' => $eventData['attendeesEntityList'] ?? [],
				'meeting' => $eventData['MEETING'] ?? [],
				'location' => $eventData['LOCATION'] ?? [],
				'timezoneFrom' => $eventData['TZ_FROM'],
				'timezoneTo' => $eventData['TZ_TO'],
				'from' => $from * $milliseconds,
				'to' => $to * $milliseconds,
				'duration' => $eventData['DT_LENGTH'] * $milliseconds,
				'sectionId' => $this->activityData['settings']['CALENDAR_SECTION_ID'] ?? null,
			];

			if (
				isset($this->activityData['settings']['ADDRESS_FORMATTED'])
				&& $this->activityData['settings']['ADDRESS_FORMATTED'] === $result['location']
			)
			{
				if (isset($this->activityData['settings']['LOCATION']))
				{
					$result['location'] = $this->activityData['settings']['LOCATION'];
				}
				else
				{
					unset($result['location']);
				}
			}
		}

		return $result;
	}

	public function prepareEntity(OptionallyConfigurable $entity, bool $skipActiveSectionCheck = false): void
	{
		if (isset($this->blockData['from']))
		{
			$start = (DateTime::createFromTimestamp($this->blockData['from'] / 1000))->toString();
			$end = (DateTime::createFromTimestamp($this->blockData['to'] / 1000))->toString();

			$fields = [
				'DEADLINE' => $start,
				'START_TIME' => $start,
				'END_TIME' => $end,
			];

			$selectedUserIds = $this->blockData['selectedUserIds'] ?? null;
			if (!empty($selectedUserIds) && is_array($selectedUserIds))
			{
				$fields['IS_MEETING'] = true;
				$fields['ATTENDEES_CODES'] = array_map(static fn($userId) => 'U' . $userId, $selectedUserIds);

				$settings = $fields['SETTINGS'] ?? [];
				$settings['USERS'] = $selectedUserIds;
				$fields['SETTINGS'] = $settings;
			}

			$location = $this->blockData['location'] ?? null;
			if (!empty($location))
			{
				$fields['LOCATION'] = $location;

				$settings = $fields['SETTINGS'] ?? [];
				$settings['LOCATION'] = $location;
				$fields['SETTINGS'] = $settings;

				$this->addLocationToEventDescription($fields, $location);
			}

			$sectionId = $this->blockData['sectionId'] ?? null;
			if (!empty($sectionId))
			{
				$fields['SECTION_ID'] = $sectionId;

				$settings = $fields['SETTINGS'] ?? [];
				$settings['CALENDAR_SECTION_ID'] = $sectionId;
				$fields['SETTINGS'] = $settings;
			}

			$entity->appendAdditionalFields($fields);

			return;
		}

		$calendarEventId = $entity->getCalendarEventId();

		if (!$calendarEventId || !$this->isCalendarIncluded)
		{
			return;
		}

		if (!$skipActiveSectionCheck && empty($this->blockData['active']))
		{
			$this->unbindAndDeleteCalendarEvent($entity, $calendarEventId);

			return;
		}

		$eventData = \Bitrix\Crm\Integration\Calendar::getEvent($calendarEventId);
		$deadline = clone($entity->getDeadline());
		$start = $deadline->toString();

		$fields = [
			'DEADLINE' => $start,
			'START_TIME' => $start,
			'END_TIME' => $deadline->add('PT' . $eventData['DT_LENGTH'] . 'S')->toString(),
		];

		// @todo changing existing activity
		/*$selectedUserIds = $this->blockData['selectedUserIds'] ?? null;
		if (!empty($selectedUserIds) && is_array($selectedUserIds))
		{
			$fields['IS_MEETING'] = true;
			$fields['ATTENDEES_CODES'] = array_map(static fn($userId) => 'U'.$userId, $selectedUserIds);
		}

		$location = $this->blockData['location'] ?? null;
		if (!empty($location))
		{
			$fields['LOCATION'] = $location;
		}*/

		$entity->appendAdditionalFields($fields);
	}

	private function unbindAndDeleteCalendarEvent(OptionallyConfigurable $entity, int $calendarEventId): void
	{
		$entity->setCalendarEventId(0);
		$entity->save();

		CCalendarEvent::Delete([
			'id' => $calendarEventId,
			'bMarkDeleted' => true,
		]);
	}

	private function addLocationToEventDescription(array &$fields, string $locationId): void
	{
		if (!Loader::includeModule('calendar'))
		{
			return;
		}

		$location = \Bitrix\Calendar\Rooms\Util::parseLocation($locationId);
		$sectionList = \Bitrix\Calendar\Rooms\Manager::getRoomsList();

		$locationItem = null;
		foreach($sectionList as $room)
		{
			if ((int)$room['ID'] === (int)$location['room_id'])
			{
				$locationItem = $room;
				break;
			}
		}

		if (!$locationItem)
		{
			return;
		}

		$fields['CALENDAR_ADDITIONAL_DESCRIPTION_DATA'] = $fields['CALENDAR_ADDITIONAL_DESCRIPTION_DATA'] ?? [];
		$fields['CALENDAR_ADDITIONAL_DESCRIPTION_DATA']['CALENDAR_LOCATION'] = [
			'TITLE' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_TODO_CALENDAR_LOCATION'),
			'ITEMS' => [
				$locationItem['NAME'],
			],
		];
	}

	public function getOptions(OptionallyConfigurable $entity): array
	{
		return [
			'SKIP_CURRENT_CALENDAR_EVENT' => false,
		];
	}
}
