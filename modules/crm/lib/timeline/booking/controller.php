<?php

namespace Bitrix\Crm\Timeline\Booking;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Timeline;

final class Controller extends Timeline\Controller
{
	public function onBookingCreated(array $bindings, array $booking): ?int
	{
		return $this->handleBookingEvent(
			Timeline\LogMessageType::BOOKING_CREATED,
			Timeline\TimelineType::LOG_MESSAGE,
			$bindings,
			$booking
		);
	}

	private function handleBookingEvent(
		int $typeCategoryId,
		int $typeId,
		array $bindings,
		array $booking): ?int
	{
		$timelineEntryId = $this->getTimelineEntryFacade()->create(
			Timeline\TimelineEntry\Facade::BOOKING,
			[
				'TYPE_ID' => $typeId,
				'TYPE_CATEGORY_ID' => $typeCategoryId,
				'AUTHOR_ID' => $booking['createdBy'],
				'SETTINGS' => $booking,
				'BINDINGS' => $bindings,
				'ASSOCIATED_ENTITY_ID' => $booking['id'],
			],
		);

		if (!$timelineEntryId)
		{
			return null;
		}

		foreach ($bindings as $binding)
		{
			$identifier = new ItemIdentifier($binding['OWNER_TYPE_ID'], $binding['OWNER_ID']);

			$this->sendPullEventOnAdd($identifier, $timelineEntryId);
		}

		return $timelineEntryId;
	}
}
