<?php

namespace Bitrix\Crm\Integration\Calendar;

use Bitrix\Crm\Badge\SourceIdentifier;
use Bitrix\Crm\Badge\Type\CalendarSharingStatus;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Monitor;
use Bitrix\Crm\Timeline\CalendarSharing\Controller;
use Bitrix\Crm\Timeline\LogMessageType;
use Bitrix\Main\Event;

class CalendarSharingTimeline
{
	public const EVENT_TYPE_NOT_VIEWED = 'notViewed';
	public const EVENT_TYPE_VIEWED = 'viewed';
	public const EVENT_TYPE_EVENT_CONFIRMED = 'eventConfirmed';
	public const EVENT_TYPE_EVENT_CREATED = 'eventCreated';

	public const SHARING_CRM_DEAL_LINK_TYPE = 'crm_deal';

	public const ACCEPTED_BADGE_TYPES = [
		EventData::SHARING_ON_NOT_VIEWED,
		EventData::SHARING_ON_VIEWED,
		EventData::SHARING_ON_EVENT_CONFIRMED,
	];

	/**
	 * @param Event $event
	 * @return void
	 */
	public static function onSharedCrmActions(Event $event)
	{
		if (!Helper::isSharingCrmAvaible())
		{
			return;
		}

		$eventData = self::prepareEventData($event);

		if (!$eventData)
		{
			return;
		}

		self::createTimelineEntry($eventData);

		if (in_array($eventData->getEventType(), self::ACCEPTED_BADGE_TYPES, true))
		{
			self::syncBadge($eventData);
		}
	}

	/**
	 * @param EventData $eventData
	 * @return void
	 */
	public static function createTimelineEntry(EventData $eventData)
	{
		$item = Container::getInstance()
			->getFactory($eventData->getEntityTypeId())
			->getItem($eventData->getEntityId())
		;

		if (!$item)
		{
			return;
		}

		$controller = Controller::getInstance();
		$itemIdentifier = ItemIdentifier::createByItem($item);

		switch ($eventData->getEventType())
		{
			case EventData::SHARING_ON_NOT_VIEWED:
				$controller->onNotViewed($itemIdentifier, $eventData);
				break;
			case EventData::SHARING_ON_VIEWED:
				$controller->onViewed($itemIdentifier, $eventData);
				break;
			case EventData::SHARING_ON_EVENT_CREATED:
				$controller->onEventCreated($itemIdentifier, $eventData);
				break;
			case EventData::SHARING_ON_EVENT_DOWNLOADED:
				$controller->onEventDownloaded($itemIdentifier, $eventData);
				break;
			case EventData::SHARING_ON_INVITATION_SENT:
				$controller->onInvitationSent($itemIdentifier, $eventData);
				break;
			case EventData::SHARING_ON_EVENT_CONFIRMED:
				$controller->onEventConfirmed($itemIdentifier, $eventData);
				break;
			case EventData::SHARING_ON_LINK_COPIED:
				$controller->onLinkCopied($itemIdentifier, $eventData);
				break;
			case EventData::SHARING_ON_RULE_UPDATED:
				$controller->onRuleUpdated($itemIdentifier, $eventData);
				break;
		}
	}

	/**
	 * @param EventData $eventData
	 * @return void
	 */
	public static function syncBadge(EventData $eventData)
	{
		$badgeType = null;

		switch ($eventData->getEventType())
		{
			case EventData::SHARING_ON_NOT_VIEWED:
				$badgeType = CalendarSharingStatus::SLOTS_NOT_VIEWED;
				break;
			case EventData::SHARING_ON_VIEWED:
				$badgeType = CalendarSharingStatus::SLOTS_VIEWED;
				break;
			case EventData::SHARING_ON_EVENT_CONFIRMED:
				$badgeType = CalendarSharingStatus::EVENT_CONFIRMED;
				break;
		}

		$badge = Container::getInstance()->getBadge(
			CalendarSharingStatus::CALENDAR_SHARING_STATUS_TYPE,
			$badgeType
		);

		$itemIdentifier = new ItemIdentifier($eventData->getEntityTypeId(), $eventData->getEntityId());
		$sourceIdentifier = new SourceIdentifier(
			SourceIdentifier::CALENDAR_SHARING_TYPE_PROVIDER,
			0,
			$eventData->getLinkId()
		);

		$badge->upsert($itemIdentifier, $sourceIdentifier);

		Monitor::getInstance()->onBadgesSync($itemIdentifier);
	}

	/**
	 * @param Event $event
	 * @return EventData|null
	 */
	private static function prepareEventData(Event $event): ?EventData
	{
		$eventData = new EventData();

		switch ($event->getParameter('EVENT_TYPE'))
		{
			case self::EVENT_TYPE_NOT_VIEWED:
				$eventData->setEventType(EventData::SHARING_ON_NOT_VIEWED);
				break;
			case self::EVENT_TYPE_VIEWED:
				$eventData->setEventType(EventData::SHARING_ON_VIEWED);
				break;
			case self::EVENT_TYPE_EVENT_CONFIRMED:
				$eventData->setEventType(EventData::SHARING_ON_EVENT_CONFIRMED);
				break;
			case self::EVENT_TYPE_EVENT_CREATED:
				$eventData->setEventType(EventData::SHARING_ON_EVENT_CREATED);
				break;
		}

		if ($event->getParameter('LINK_TYPE') === self::SHARING_CRM_DEAL_LINK_TYPE)
		{
			$eventData->setEntityTypeId(\CCrmOwnerType::Deal);
		}

		if (!$eventData->getEventType() || !$eventData->getEntityTypeId())
		{
			return null;
		}

		if ($eventData->getEntityTypeId() === LogMessageType::CALENDAR_SHARING_LINK_COPIED)
		{
			$eventData->setLinkHash($event->getParameter('LINK_HASH'));
		}

		$eventData
			->setOwnerId($event->getParameter('OWNER_ID'))
			->setEntityId($event->getParameter('LINK_ENTITY_ID'))
			->setLinkId($event->getParameter('LINK_ID'))
			->setContactTypeId($event->getParameter('CONTACT_TYPE_ID'))
			->setContactId($event->getParameter('CONTACT_ID'))
			->setTimestamp($event->getParameter('TIMESTAMP'))
			->setAssociatedEntityId($event->getParameter('ASSOCIATED_ENTITY_ID'))
			->setAssociatedEntityTypeId($event->getParameter('ASSOCIATED_ENTITY_TYPE_ID'))
		;

		return $eventData;
	}
}