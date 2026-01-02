<?php

declare(strict_types=1);

namespace Bitrix\Crm\Timeline\Booking;

use Bitrix\Crm\Activity\Provider\Booking\BookingCommon;
use Bitrix\Crm\Dto\Booking\Booking\BookingStatusEnum;
use Bitrix\Crm\Dto\Booking\Message\Message;
use Bitrix\Crm\Dto\Booking\WaitListItem\WaitListItemFields;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Dto\Booking\Booking\BookingFields;
use Bitrix\Crm\Timeline;

final class Controller extends Timeline\Controller
{
	public function onBookingCreated(array $bindings, BookingFields $booking): ?int
	{
		return $this->handleBookingEvent(
			Timeline\LogMessageType::BOOKING_CREATED,
			Timeline\TimelineType::LOG_MESSAGE,
			$bindings,
			[
				'booking' => $booking->toArray(),
			]
		);
	}

	public function onMessageStatusUpdate(
		BookingFields $booking,
		Message $message,
		array $messageInfo,
	): ?int
	{
		return $this->handleBookingEvent(
			Timeline\LogMessageType::BOOKING_MESSAGE_STATUS_UPDATE,
			Timeline\TimelineType::LOG_MESSAGE,
			BookingCommon::makeBindings($booking),
			[
				'booking'=> $booking->toArray(),
				'message' => $message->toArray(),
				'messageInfo' => $messageInfo,
			]
		);
	}

	public function onBookingStatusUpdated(
		BookingFields $booking,
		BookingStatusEnum $status,
	): ?int
	{
		return $this->handleBookingEvent(
			Timeline\LogMessageType::BOOKING_STATUS_UPDATE,
			Timeline\TimelineType::LOG_MESSAGE,
			BookingCommon::makeBindings($booking),
			[
				'booking'=> $booking->toArray(),
				'status' => $status->value,
			]
		);
	}

	public function onBookingCreationError(array $bindings, array $settings): ?int
	{
		$timelineEntryId = $this->getTimelineEntryFacade()->create(
			Timeline\TimelineEntry\Facade::BOOKING,
			[
				'TYPE_ID' => Timeline\TimelineType::LOG_MESSAGE,
				'TYPE_CATEGORY_ID' => Timeline\LogMessageType::BOOKING_CREATION_ERROR,
				'BINDINGS' => $bindings,
				'SETTINGS' => $settings,
			],
		);

		if ($timelineEntryId)
		{
			foreach ($bindings as $binding)
			{
				$identifier = new ItemIdentifier($binding['OWNER_TYPE_ID'], $binding['OWNER_ID']);

				$this->sendPullEventOnAdd($identifier, $timelineEntryId);
			}
		}

		return $timelineEntryId;
	}

	private function handleBookingEvent(
		int $typeCategoryId,
		int $typeId,
		array $bindings,
		array $settings
	): ?int
	{
		$timelineEntryId = $this->getTimelineEntryFacade()->create(
			Timeline\TimelineEntry\Facade::BOOKING,
			[
				'TYPE_ID' => $typeId,
				'TYPE_CATEGORY_ID' => $typeCategoryId,
				'AUTHOR_ID' => isset($settings['booking']['createdBy']) ? (int)$settings['booking']['createdBy'] : 0,
				'SETTINGS' => $settings,
				'BINDINGS' => $bindings,
				'ASSOCIATED_ENTITY_ID' => isset($settings['booking']['id']) ? (int)$settings['booking']['id'] : 0,
			],
		);

		if (!$timelineEntryId)
		{
			return null;
		}

		foreach ($bindings as $binding)
		{
			$identifier = new ItemIdentifier((int)$binding['OWNER_TYPE_ID'], (int)$binding['OWNER_ID']);

			$this->sendPullEventOnAdd($identifier, $timelineEntryId);
		}

		return $timelineEntryId;
	}

	public function onWaitListItemCreated(array $bindings, WaitListItemFields $waitListItem): ?int
	{
		return $this->createWaitListItemTimelineEntry(
			typeCategoryId: Timeline\LogMessageType::WAIT_LIST_ITEM_CREATED,
			bindings: $bindings,
			settings: $waitListItem->toArray(),
			authorId: $waitListItem->createdBy,
			entityId: $waitListItem->id,
		);
	}

	public function onWaitListItemDeleted(array $bindings, WaitListItemFields $waitListItem, int $removedBy): ?int
	{
		return $this->createWaitListItemTimelineEntry(
			typeCategoryId: Timeline\LogMessageType::WAIT_LIST_ITEM_DELETED,
			bindings: $bindings,
			settings: $waitListItem->toArray(),
			authorId: $removedBy,
			entityId: $waitListItem->id,
		);
	}

	private function createWaitListItemTimelineEntry(
		int $typeCategoryId,
		array $bindings,
		array $settings,
		int $authorId,
		int $entityId,
	): ?int
	{
		$timelineEntryId = $this->getTimelineEntryFacade()->create(
			Timeline\TimelineEntry\Facade::WAIT_LIST_ITEM,
			[
				'TYPE_ID' => Timeline\TimelineType::LOG_MESSAGE,
				'TYPE_CATEGORY_ID' => $typeCategoryId,
				'AUTHOR_ID' => $authorId,
				'SETTINGS' => $settings,
				'BINDINGS' => $bindings,
				'ASSOCIATED_ENTITY_ID' => $entityId,
			],
		);

		if ($timelineEntryId)
		{
			foreach ($bindings as $binding)
			{
				$identifier = new ItemIdentifier((int)$binding['OWNER_TYPE_ID'], (int)$binding['OWNER_ID']);

				$this->sendPullEventOnAdd($identifier, $timelineEntryId);
			}
		}

		return $timelineEntryId ?: null;
	}
}
