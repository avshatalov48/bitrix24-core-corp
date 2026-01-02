<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Integration\Calendar;

use Bitrix\Booking\Entity\Client\Client;
use Bitrix\Booking\Entity\DatePeriod;
use Bitrix\Booking\Entity\Resource\Resource;
use Bitrix\Booking\Internals\Model\ResourceLinkedEntityData\CalendarDataRemindersDto;
use Bitrix\Calendar\Core\Base\Date;
use Bitrix\Calendar\Core\Builders\EventBuilderFromArray;
use Bitrix\Calendar\Core\Event\Event;
use Bitrix\Calendar\Core\Event\Tools\Dictionary;
use Bitrix\Calendar\Core\Mappers;
use Bitrix\Calendar\Util;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

class CalendarEvent
{
	private Mappers\Event $eventMapper;

	public function __construct(
		Mappers\Event|null $eventMapper = null,
	)
	{
		if ($eventMapper)
		{
			$this->eventMapper = $eventMapper;
		}
		else if ($this->isAvailable())
		{
			$this->eventMapper = new Mappers\Event();
		}
	}

	/**
	 * @param CalendarDataRemindersDto[] $reminders
	 */
	public function create(
		int $creatorId,
		array $userIds,
		DatePeriod $datePeriod,
		Resource $resource,
		Client|null $client = null,
		int|null $locationId = null,
		array|null $reminders = null,
	): int|null
	{
		if (!$this->isAvailable())
		{
			return null;
		}

		$event = $this->prepareEvent(
			datePeriod: $datePeriod,
			hostId: $userIds[0],
			meetingCreator: $creatorId,
			userIds: $userIds,
			locationId: $locationId,
			reminders: $reminders ?? $this->getDefaultReminders(),
			params: [
				'eventName' => $this->createEventName($resource, $client),
				'description' => '',
			]
		);
		$event = $this->eventMapper->create($event, [
			'sendInvitations' => false,
		]);

		$eventId = $event?->getId();
		if ($eventId)
		{
			$this->setEventAccepted($eventId, $userIds);
		}

		return $eventId;
	}

	/**
	 * @param int[]|null $userIds
	 * @param CalendarDataRemindersDto[]|null $reminders
	 */
	public function update(
		int $eventId,
		DatePeriod $newDatePeriod,
		Resource $resource,
		Client|null $client = null,
		array|null $userIds = null,
		int|null $locationId = null,
		array|null $reminders = null,
	): void
	{
		if (!$this->isAvailable())
		{
			return;
		}

		/** @var Event $event */
		$event = $this->eventMapper->getById($eventId);
		if (!$event)
		{
			return;
		}

		$eventFields = $this->eventMapper->convertToArray($event);

		$dateFrom = $this->prepareDate($newDatePeriod->getDateFrom());
		$dateTo = $this->prepareDate($newDatePeriod->getDateTo());

		$eventFields['NAME'] = $this->createEventName($resource, $client);
		$eventFields['TZ_FROM'] = $dateFrom->getTimezone()->getName();
		$eventFields['TZ_TO'] = $dateTo->getTimezone()->getName();
		if ($locationId)
		{
			$eventFields['LOCATION'] = $this->prepareLocation($locationId);
		}

		if ($reminders !== null)
		{
			$eventFields['REMIND'] = array_map(static fn(CalendarDataRemindersDto $reminder) => $reminder->toArray(), $reminders);
		}

		if ($userIds)
		{
			$eventFields['ATTENDEES_CODES'] = $this->prepareAttendeeCodes($userIds);
		}

		$event = (new EventBuilderFromArray($eventFields))->build();
		$this->setDatesToEvent($event, $newDatePeriod);
		$this->eventMapper->update($event);
		$this->setEventAccepted($eventId, $userIds);
	}

	public function delete(int $eventId): void
	{
		if (!$this->isAvailable())
		{
			return;
		}

		$event = $this->eventMapper->getById($eventId);
		if (!$event)
		{
			return;
		}

		$this->eventMapper->delete($event);
	}

	public function getEventHostId(int $eventId): int|null
	{
		/** @var Event $event */
		$event = $this->eventMapper->getById($eventId);
		if (!$event)
		{
			return null;
		}

		return $event->getEventHost()?->getId();
	}

	private function prepareEvent(
		DatePeriod $datePeriod,
		int $hostId,
		int $meetingCreator,
		array $userIds,
		int|null $locationId = null,
		array|null $reminders = null,
		array $params = [],
	): Event
	{
		$meeting = [
			'HOST_NAME' => \CCalendar::GetUserName($hostId),
			'NOTIFY' => true,
			'REINVITE' => false,
			'ALLOW_INVITE' => true,
			'MEETING_CREATOR' => $meetingCreator,
			'HIDE_GUESTS' => false,
		];

		$dateFrom = $this->prepareDate($datePeriod->getDateFrom());
		$dateTo = $this->prepareDate($datePeriod->getDateTo());

		$eventData = [
			'NAME' => (string)($params['eventName'] ?? ''),
			'TZ_FROM' => $dateFrom->getTimezone()->getName(),
			'TZ_TO' => $dateTo->getTimezone()->getName(),
			'SKIP_TIME' => 'N',
			'ACCESSIBILITY' => 'busy',
			'IMPORTANCE' => 'normal',
			'MEETING_HOST' => $hostId,
			'IS_MEETING' => true,
			'MEETING' => $meeting,
			'DESCRIPTION' => (string)($params['description'] ?? ''),
		];

		if ($locationId)
		{
			$eventData['LOCATION'] = $this->prepareLocation($locationId);
		}

		if ($reminders !== null)
		{
			$eventData['REMIND'] = $reminders
				? array_map(
					static fn(CalendarDataRemindersDto $reminder) => $reminder->toArray(),
					$reminders,
				)
				: []
			;
		}

		$section = \CCalendarSect::GetSectionForOwner(Dictionary::CALENDAR_TYPE['user'], $hostId);

		$eventData = [
			...$eventData,
			'OWNER_ID' => $hostId,
			'SECTIONS' => [$section['sectionId']],
			'ATTENDEES_CODES' => $this->prepareAttendeeCodes($userIds),
			'EVENT_TYPE' => Dictionary::EVENT_TYPE['booking'],
		];

		$event = (new EventBuilderFromArray($eventData))->build();
		$this->setDatesToEvent($event, $datePeriod);

		return $event;
	}

	/**
	 * @param int[] $userIds
	 */
	private function prepareAttendeeCodes(array $userIds): array
	{
		$attendeesCodes = [];

		foreach ($userIds as $userId)
		{
			$attendeesCodes[] = 'U' . $userId;
		}

		return $attendeesCodes;
	}

	private function isAvailable(): bool
	{
		return Loader::includeModule('calendar');
	}

	private function setEventAccepted(int $eventId, array $userIds): void
	{
		foreach ($userIds as $userId)
		{
			\CCalendarEvent::SetMeetingStatus([
				'eventId' => $eventId,
				'userId' => $userId,
				'status' => Dictionary::MEETING_STATUS['Yes'],
				'sharingAutoAccept' => true,
				'hostNotification' => false,
			]);
		}
	}

	private function getClientName(Client $client): string|null
	{
		return $client->getData()['name'] ?? null;
	}

	private function createEventName(Resource $resource, Client|null $client = null): string
	{
		if ($client && ($clientName = $this->getClientName($client)))
		{
			return Loc::getMessage(
				'BOOKING_INTEGRATION_CALENDAR_EVENT_NAME_CLIENT_TPL',
				[
					'#RESOURCE_TYPE#' => $resource->getType()?->getName(),
					'#RESOURCE_NAME#' => $resource->getName(),
					'#CLIENT_NAME#' => $clientName,
				]
			) ?? '';
		}

		return Loc::getMessage(
			'BOOKING_INTEGRATION_CALENDAR_EVENT_NAME_TPL',
			[
				'#RESOURCE_TYPE#' => $resource->getType()?->getName(),
				'#RESOURCE_NAME#' => $resource->getName(),
			],
		) ?? '';
	}

	private function getDefaultReminders(): array
	{
		return [
			new CalendarDataRemindersDto('min', 15),
			new CalendarDataRemindersDto('min', 60),
		];
	}

	private function prepareLocation(int $locationId): string
	{
		return 'calendar_' . $locationId;
	}

	private function prepareDate(\DateTimeImmutable $date): \DateTimeImmutable
	{
		$timezone = $date->getTimezone()->getName();
		if (Util::isTimezoneValid($timezone))
		{
			return $date;
		}

		return $date->setTimezone(new \DateTimeZone('UTC'));
	}

	private function setDatesToEvent(Event $event, DatePeriod $datePeriod): void
	{
		$event
			->setStart(new Date(DateTime::createFromPhp(\DateTime::createFromImmutable($datePeriod->getDateFrom()))))
			->setEnd(new Date(DateTime::createFromPhp(\DateTime::createFromImmutable($datePeriod->getDateTo()))))
		;
	}
}
