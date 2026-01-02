<?php

declare(strict_types=1);

namespace Bitrix\Crm\Activity\Provider\Booking;

use Bitrix\Crm\Badge\Badge;
use Bitrix\Crm\Badge\SourceIdentifier;
use Bitrix\Crm\Badge\Type\BookingStatus;
use Bitrix\Crm\Dto\Booking\Booking\BookingFields;
use Bitrix\Crm\Dto\Booking\Booking\BookingStatusEnum;
use Bitrix\Crm\Dto\Booking\Message\Message;
use Bitrix\Crm\Dto\Booking\Message\MessageStatusEnum;
use Bitrix\Crm\Dto\Booking\Message\MessageTypeEnum;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;

class BookingBadge
{
	public function updateKanbanBadgeByActivityData(array|null $activitySettings): void
	{
		if (!is_array($activitySettings))
		{
			return;
		}

		$messageData = $activitySettings['MESSAGE'] ?? null;
		$message = $messageData ? Message::mapFromArray($messageData) : null;
		$statusData = $activitySettings['STATUS'] ?? null;
		$status = $statusData ? BookingStatusEnum::tryFrom($statusData) : null;
		$statusUpdatedAt = $activitySettings['STATUS_UPDATED'] ?? null;

		$bookingFields = BookingFields::tryFrom($activitySettings['FIELDS'] ?? null);
		if (!$bookingFields)
		{
			return;
		}

		$this->updateOrClearBadge(
			booking: $bookingFields,
			status: $status,
			statusUpdatedAt: $statusUpdatedAt,
			message: $message,
		);
	}

	public function updateOrClearBadge(
		BookingFields $booking,
		BookingStatusEnum $status = null,
		int $statusUpdatedAt = null,
		Message $message = null,
	): void
	{
		$isUpdated = $this->updateKanbanBadge($booking, $status, $statusUpdatedAt, $message);
		if (!$isUpdated)
		{
			$this->clearKanbanBadge($booking->getId(), $status);
		}
	}

	public function clearKanbanBadge(int $sourceId, BookingStatusEnum|null $status = null): void
	{
		// canceled by client badge should always stay on kanban
		// cause manager should see potential problem with booking
		if ($status === BookingStatusEnum::CanceledByClient)
		{
			return;
		}

		$sourceIdentifier = new SourceIdentifier(
			SourceIdentifier::BOOKING_BOOKING_TYPE_PROVIDER,
			0,
			$sourceId
		);

		Badge::deleteBySource($sourceIdentifier);
	}

	private function updateKanbanBadge(
		BookingFields $booking,
		BookingStatusEnum $status = null,
		int $statusUpdatedAt = null,
		Message $message = null,
	): bool
	{
		$badgeType = $this->getBadgeType($message, $status, $statusUpdatedAt);
		if (!$badgeType)
		{
			return false;
		}

		$badge = Container::getInstance()->getBadge(
			Badge::BOOKING_STATUS_TYPE,
			$badgeType
		);

		$sourceIdentifier = new SourceIdentifier(
			SourceIdentifier::BOOKING_BOOKING_TYPE_PROVIDER,
			0,
			$booking->getId()
		);

		$bindings = BookingCommon::makeBindings($booking);

		foreach ($bindings as $binding)
		{
			$itemIdentifier = new ItemIdentifier($binding['OWNER_TYPE_ID'], $binding['OWNER_ID']);

			$badge->upsert($itemIdentifier, $sourceIdentifier);
		}

		return true;
	}

	private function getBadgeType(
		Message $message = null,
		BookingStatusEnum $status = null,
		int $statusUpdatedAt = null
	): string|null
	{
		if (!$message && !$status)
		{
			return null;
		}

		if ($message && $message->timestamp > $statusUpdatedAt)
		{
			return $this->getBadgeTypeByMessage($message);
		}

		return $this->getBadgeTypeByStatus($status, $message);
	}

	private function getBadgeTypeByMessage(Message $message): string|null
	{
		return match ($message->type)
		{
			MessageTypeEnum::Confirmation => match ($message->status)
			{
				MessageStatusEnum::Sent => BookingStatus::CONFIRMATION_SEND_NOT_READ,
				MessageStatusEnum::Read => BookingStatus::CONFIRMATION_OPEN_NOT_CONFIRMED,
				default => null,
			},
			MessageTypeEnum::Delayed => match ($message->status)
			{
				MessageStatusEnum::Sent => BookingStatus::DELAY_SEND_NOT_READ,
				MessageStatusEnum::Read => BookingStatus::DELAY_OPEN_NOT_CONFIRMED,
				default => null,
			},
			default => null,
		};
	}

	private function getBadgeTypeByStatus(BookingStatusEnum $status, Message|null $message = null): string|null
	{
		return match ($status)
		{
			BookingStatusEnum::ComingSoon => BookingStatus::COMING_SOON,
			BookingStatusEnum::CanceledByClient => BookingStatus::CANCELED_BY_CLIENT,
			// send to kanban only if confirmed by delay message
			BookingStatusEnum::ConfirmedByClient => match ($message?->type)
			{
				MessageTypeEnum::Delayed => BookingStatus::DELAY_CONFIRMED,
				default => null,
			},
			default => null,
		};
	}
}
