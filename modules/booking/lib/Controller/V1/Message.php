<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1;

use Bitrix\Booking\Entity\Message\BookingMessage;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Service\Notifications\MessageSender;
use Bitrix\Booking\Internals\Service\Notifications\NotificationType;
use Bitrix\Booking\Internals\Repository\BookingRepositoryInterface;
use Bitrix\Booking\Provider\Params\Booking\BookingFilter;
use Bitrix\Booking\Provider\Params\Booking\BookingSelect;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;

class Message extends BaseController
{
	private BookingRepositoryInterface $bookingRepository;
	private MessageSender $messageSender;

	public function __construct(Request $request = null)
	{
		parent::__construct($request);

		$this->bookingRepository = Container::getBookingRepository();
		$this->messageSender = Container::getMessageSender();
	}

	public function sendAction(int $bookingId, string $notificationType): BookingMessage|null
	{
		$notificationType = NotificationType::tryFrom($notificationType);
		if (!$notificationType)
		{
			$this->addError(new Error(Loc::getMessage('BOOKING_CONTROLLER_MESSAGE_ERROR')));

			return null;
		}

		$booking = $this->bookingRepository->getList(
			limit: 1,
			filter: new BookingFilter([
				'ID' => $bookingId,
				'HAS_CLIENTS' => true,
				'HAS_RESOURCES' => true,
			]),
			select: (new BookingSelect([
				'EXTERNAL_DATA',
				'CLIENTS',
				'RESOURCES',
			]))->prepareSelect(),
		)->getFirstCollectionItem();

		if (!$booking)
		{
			$this->addError(new Error(Loc::getMessage('BOOKING_CONTROLLER_MESSAGE_ERROR')));

			return null;
		}

		$sendResult = $this->messageSender->send($booking, $notificationType);
		if (!$sendResult->isSuccess())
		{
			$this->addError(new Error(Loc::getMessage('BOOKING_CONTROLLER_MESSAGE_ERROR')));

			return null;
		}

		return (new BookingMessage())
			->setBookingId($bookingId)
			->setNotificationType($notificationType)
		;
	}
}
