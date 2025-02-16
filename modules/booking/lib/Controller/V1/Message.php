<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1;

use Bitrix\Booking\Controller\V1\Response\MessageSendResponse;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Notifications\BookingMessageCreatorFactory;
use Bitrix\Booking\Internals\NotificationType;
use Bitrix\Booking\Internals\Query\Booking\GetListFilter;
use Bitrix\Booking\Internals\Query\Booking\GetListSelect;
use Bitrix\Booking\Internals\Repository\BookingRepositoryInterface;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;

class Message extends BaseController
{
	private BookingRepositoryInterface $bookingRepository;

	public function __construct(Request $request = null)
	{
		parent::__construct($request);

		$this->bookingRepository = Container::getBookingRepository();
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
			filter: new GetListFilter([
				'ID' => $bookingId,
				'HAS_CLIENTS' => true,
				'HAS_RESOURCES' => true,
			]),
			select: new GetListSelect([
				'EXTERNAL_DATA',
				'CLIENTS',
				'RESOURCES',
			]),
		)->getFirstCollectionItem();
		if (!$booking)
		{
			return $this->getErrorResponse();
		}

		$sendResult = BookingMessageCreatorFactory::create($booking)->setBooking($booking)
			->createMessageOfType($notificationType)
			?->send($booking);

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
