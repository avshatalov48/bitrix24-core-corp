<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1;

use Bitrix\Booking\Controller\V1\Response\MessageSendResponse;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Service\Notifications\MessageSender;
use Bitrix\Booking\Internals\Service\Notifications\NotificationType;
use Bitrix\Booking\Internals\Repository\BookingRepositoryInterface;
use Bitrix\Booking\Provider\Params\Booking\BookingFilter;
use Bitrix\Booking\Provider\Params\Booking\BookingSelect;
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

	public function sendAction(int $bookingId, string $notificationType): MessageSendResponse
	{
		$notificationType = NotificationType::tryFrom($notificationType);
		if (!$notificationType)
		{
			return $this->getErrorResponse();
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
			return $this->getErrorResponse();
		}

		$sendResult = $this->messageSender->send($booking, $notificationType);
		if (!$sendResult->isSuccess())
		{
			return $this->getErrorResponse();
		}

		return new MessageSendResponse(isSuccess: true);
	}

	private function getErrorResponse(): MessageSendResponse
	{
		return new MessageSendResponse(
			isSuccess: false,
			errorText: Loc::getMessage('BOOKING_CONTROLLER_MESSAGE_ERROR'),
		);
	}
}
