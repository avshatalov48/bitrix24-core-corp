<?php

namespace Bitrix\Crm\Timeline\CalendarSharing;

use Bitrix\Crm\Integration\Calendar\EventData;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Timeline;
use Bitrix\Crm\Service;

final class Controller extends Timeline\Controller
{
	public function onNotViewed(ItemIdentifier $identifier, EventData $eventData): ?int
	{
		return $this->handleCalendarSharingEvent(
			Timeline\LogMessageType::CALENDAR_SHARING_NOT_VIEWED,
			Timeline\TimelineType::LOG_MESSAGE,
			$identifier,
			$eventData
		);
	}

	public function onViewed(ItemIdentifier $identifier, EventData $eventData): ?int
	{
		return $this->handleCalendarSharingEvent(
			Timeline\LogMessageType::CALENDAR_SHARING_VIEWED,
			Timeline\TimelineType::LOG_MESSAGE,
			$identifier,
			$eventData
		);
	}

	public function onEventCreated(ItemIdentifier $identifier, EventData $eventData): ?int
	{
		return $this->handleCalendarSharingEvent(
			Timeline\LogMessageType::CALENDAR_SHARING_EVENT_CREATED,
			Timeline\TimelineType::LOG_MESSAGE,
			$identifier,
			$eventData
		);
	}

	public function onEventDownloaded(ItemIdentifier $identifier, EventData $eventData): ?int
	{
		return $this->handleCalendarSharingEvent(
			Timeline\LogMessageType::CALENDAR_SHARING_EVENT_DOWNLOADED,
			Timeline\TimelineType::LOG_MESSAGE,
			$identifier,
			$eventData
		);
	}

	public function onInvitationSent(ItemIdentifier $identifier, EventData $eventData): ?int
	{
		return $this->handleCalendarSharingEvent(
			Entry::SHARING_TYPE_INVITATION_SENT,
			Timeline\TimelineType::CALENDAR_SHARING,
			$identifier,
			$eventData
		);
	}

	public function onEventConfirmed(ItemIdentifier $identifier, EventData $eventData): ?int
	{
		return $this->handleCalendarSharingEvent(
			Timeline\LogMessageType::CALENDAR_SHARING_EVENT_CONFIRMED,
			Timeline\TimelineType::LOG_MESSAGE,
			$identifier,
			$eventData
		);
	}

	public function onLinkCopied(ItemIdentifier $identifier, EventData $eventData): ?int
	{
		return $this->handleCalendarSharingEvent(
			Timeline\LogMessageType::CALENDAR_SHARING_LINK_COPIED,
			Timeline\TimelineType::LOG_MESSAGE,
			$identifier,
			$eventData
		);
	}

	public function onRuleUpdated(ItemIdentifier $identifier, EventData $eventData): ?int
	{
		return $this->handleCalendarSharingEvent(
			Timeline\LogMessageType::CALENDAR_SHARING_RULE_UPDATED,
			Timeline\TimelineType::LOG_MESSAGE,
			$identifier,
			$eventData
		);
	}

	protected function handleCalendarSharingEvent(
		int $typeCategoryId,
		int $typeId,
		ItemIdentifier $identifier,
		EventData $eventData
	): ?int
	{
		$bindings = $this->getBindings($identifier, $eventData);

		$timelineEntry = $this->getTimelineEntryFacade()->create(
			Timeline\TimelineEntry\Facade::CALENDAR_SHARING,
			[
				'ENTITY_TYPE_ID' => $identifier->getEntityTypeId(),
				'ENTITY_ID' => $identifier->getEntityId(),
				'TYPE_ID' => $typeId,
				'TYPE_CATEGORY_ID' => $typeCategoryId,
				'AUTHOR_ID' => $eventData->getOwnerId(),
				'SETTINGS' => $this->getSettings($eventData),
				'BINDINGS' => $bindings,
				'ASSOCIATED_ENTITY_ID' => $eventData->getAssociatedEntityId(),
				'ASSOCIATED_ENTITY_TYPE_ID' => $eventData->getAssociatedEntityTypeId(),
			],
		);

		if (!$timelineEntry)
		{
			return null;
		}

		foreach ($bindings as $binding)
		{
			$this->sendPullEventOnAdd($binding, $timelineEntry);
		}

		return $timelineEntry;
	}

	protected function getSettings(EventData $eventData): array
	{
		$result = [];

		if ($eventData->getContactTypeId())
		{
			$result['CONTACT_TYPE_ID'] = $eventData->getContactTypeId();
		}

		if ($eventData->getContactId())
		{
			$result['CONTACT_ID'] = $eventData->getContactId();
		}

		if ($eventData->getTimestamp())
		{
			$result['TIMESTAMP'] = $eventData->getTimestamp();
		}

		if ($eventData->getLinkHash())
		{
			$result['LINK_HASH'] = $eventData->getLinkHash();
		}

		if ($eventData->getSharingRuleArray())
		{
			$result['LINK_RULE'] = $eventData->getSharingRuleArray();
		}

		if ($eventData->getContactCommunication())
		{
			$result['CONTACT_COMMUNICATION'] = $eventData->getContactCommunication();
		}

		if ($eventData->getChannelName())
		{
			$result['CHANNEL_NAME'] = $eventData->getChannelName();
		}

		if ($eventData->getMemberIds())
		{
			$result['MEMBER_IDS'] = $eventData->getMemberIds();
		}

		return $result;
	}

	private function getBindings(ItemIdentifier $identifier, EventData $eventData): array
	{
		$result = [];

		$result[] = $identifier;

		if ($eventData->getContactId() && $eventData->getContactTypeId())
		{
			$result[] = new ItemIdentifier(
				$eventData->getContactTypeId(),
				$eventData->getContactId()
			);
		}

		return $result;
	}

	public function prepareHistoryDataModel(array $data, array $options = null): array
	{
		$data = array_merge($data, is_array($data['SETTINGS']) ? $data['SETTINGS'] : []);

		return parent::prepareHistoryDataModel($data, $options);
	}
}