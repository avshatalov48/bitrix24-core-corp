<?php

declare(strict_types=1);

namespace Bitrix\Crm\Integration\Booking;

use Bitrix\Crm\Dto\Booking\Booking\BookingFieldsMapper;
use Bitrix\Crm\Dto\Booking\Booking\BookingStatusEnum;
use Bitrix\Crm\Dto\Booking\Message\Message;
use Bitrix\Crm\Dto\Booking\WaitListItem\WaitListItemFieldsMapper;
use Bitrix\Booking\Interfaces\ProviderInterface;
use Bitrix\Booking\Provider\BookingMessageProvider;
use Bitrix\Booking\Provider\BookingProvider;
use Bitrix\Crm\Integration\NotificationsManager;
use Bitrix\Crm\Timeline\Booking\Controller;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Crm\Activity;

class EventHandler
{
	public static function onGetProviderEventHandler(): ProviderInterface|null
	{
		if (!Loader::includeModule('booking'))
		{
			return null;
		}

		return new Provider();
	}

	public static function onBookingAdd(Event $event): void
	{
		if (!Loader::includeModule('booking'))
		{
			return;
		}

		$bookingFields = BookingFieldsMapper::mapFromBookingArray(
			booking: $event->getParameter('booking')->toArray(),
			isOverbooking: $event->getParameter('isOverbooking') ?? false,
		);
		Activity\Provider\Booking\Booking::onBookingAdded($bookingFields);
	}

	public static function onBookingUpdate(Event $event): void
	{
		if (!Loader::includeModule('booking'))
		{
			return;
		}

		$bookingFields = BookingFieldsMapper::mapFromBookingArray(
			booking: $event->getParameter('booking')->toArray(),
			isOverbooking: $event->getParameter('isOverbooking') ?? false,
		);
		Activity\Provider\Booking\Booking::onBookingUpdated($bookingFields);
	}

	public static function onBookingDelete(Event $event): void
	{
		if (!Loader::includeModule('booking'))
		{
			return;
		}

		Activity\Provider\Booking\Booking::onBookingDeleted($event->getParameter('bookingId'));
	}

	public static function onWaitListItemAdd(Event $event): void
	{
		if (!Loader::includeModule('booking'))
		{
			return;
		}

		$waitListItemFields = WaitListItemFieldsMapper::mapFromWaitListItemArray(
			$event->getParameter('waitListItem')->toArray(),
		);

		Activity\Provider\Booking\WaitListItem::onWaitListItemAdded($waitListItemFields);
	}

	public static function onWaitListItemUpdate(Event $event): void
	{
		if (!Loader::includeModule('booking'))
		{
			return;
		}

		$waitListItemFields = WaitListItemFieldsMapper::mapFromWaitListItemArray(
			$event->getParameter('waitListItem')->toArray(),
		);

		Activity\Provider\Booking\WaitListItem::onWaitListItemUpdated($waitListItemFields);
	}

	public static function onWaitListItemDelete(Event $event): void
	{
		if (!Loader::includeModule('booking'))
		{
			return;
		}

		$waitListItemFields = WaitListItemFieldsMapper::mapFromWaitListItemArray(
			$event->getParameter('waitListItem')->toArray(),
		);

		Activity\Provider\Booking\WaitListItem::onWaitListItemDeleted(
			$waitListItemFields,
			$event->getParameter('removedBy'),
		);
	}

	public static function onMessageStatusUpdate(Event $event): void
	{
		$id = (int)$event->getParameter('ID');
		if ($id <= 0)
		{
			return;
		}

		if (!Loader::includeModule('booking'))
		{
			return;
		}

		$messageSender = new MessageSender();

		$bookingMessage = (new BookingMessageProvider())->getById(
			$messageSender->getModuleId(),
			$messageSender->getCode(),
			$id,
		);
		if (!$bookingMessage)
		{
			return;
		}

		$messageStatus = (string)$event->getParameter('STATUS');
		$messageInfo = NotificationsManager::getMessageByInfoId($id);
		if (self::isMessageStatusUpdateDuplicate($messageStatus, $messageInfo['HISTORY_ITEMS'] ?? []))
		{
			return;
		}

		try
		{
			$message = Message::mapFromArray([
				'type' => $bookingMessage->getNotificationType()->value,
				'status' => $messageStatus,
				'timestamp' => time(),
			]);
		}
		catch (\Throwable)
		{
			return;
		}

		$booking = (new BookingProvider())->getById(
			(int)CurrentUser::get()->getId(),
			$bookingMessage->getBookingId(),
		)?->toArray();

		// booking may be deleted already
		if (!$booking)
		{
			return;
		}

		$bookingFields = BookingFieldsMapper::mapFromBookingArray(booking: $booking);

		if ($message->isSupported())
		{
			(new Controller())->onMessageStatusUpdate(
				$bookingFields,
				$message,
				$messageInfo,
			);
		}

		if ($message->isMeaning())
		{
			\Bitrix\Crm\Activity\Provider\Booking\Booking::onBookingMessageUpdated(
				booking: $bookingFields,
				message: $message,
			);
		}
	}

	public static function onBookingStatusUpdated(Event $event): void
	{
		if (!Loader::includeModule('booking'))
		{
			return;
		}

		$bookingData = $event->getParameter('booking')?->toArray();
		if (!$bookingData)
		{
			return;
		}

		$status = BookingStatusEnum::tryFrom($event->getParameter('status'));
		if (!$status)
		{
			return;
		}

		$bookingFields = BookingFieldsMapper::mapFromBookingArray(booking: $bookingData);

		if ($status->supportLogMessage())
		{
			(new Controller())->onBookingStatusUpdated(
				booking: $bookingFields,
				status: $status,
			);
		}

		if ($status->supportActivityStatusUpdate())
		{
			Activity\Provider\Booking\Booking::onBookingStatusUpdated(
				booking: $bookingFields,
				status: $status,
			);
		}

		if ($status->supportToDoActivity())
		{
			(new Activity\Provider\Booking\BookingToDo())->createForBooking($bookingFields, $status);
		}
	}

	private static function isMessageStatusUpdateDuplicate(string $messageStatus, array $historyItems): bool
	{
		$historyRecordsCnt = 0;

		foreach ($historyItems as $historyItem)
		{
			if ($historyItem['STATUS'] === $messageStatus)
			{
				$historyRecordsCnt++;
			}

			if ($historyRecordsCnt > 1)
			{
				return true;
			}
		}

		return false;
	}
}
