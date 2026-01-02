<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1;

use Bitrix\Booking\Controller\V1\Response\CalendarData\CalendarDataBookingInfoResponse;
use Bitrix\Booking\Internals\Exception\Booking\BookingNotFoundException;
use Bitrix\Booking\Internals\Exception\ErrorBuilder;
use Bitrix\Booking\Internals\Exception\Exception;
use Bitrix\Booking\Internals\Service\ClientService;
use Bitrix\Booking\Provider\BookingProvider;
use Bitrix\Main\Engine\CurrentUser;

class CalendarData extends BaseController
{
	public function bookingInfoAction(
		ClientService $clientService,
		CurrentUser $currentUser,
		BookingProvider $bookingProvider,
		int $bookingId,
	): CalendarDataBookingInfoResponse|null
	{
		try
		{
			$booking = $bookingProvider->getById(
				userId: (int)$currentUser->getId(),
				id: $bookingId,
				withCounters: false,
				withClientsData: true,
				withExternalData: true,
			);

			if (!$booking)
			{
				throw new BookingNotFoundException();
			}

			$clientService->applyClientPermissions($booking->getClientCollection());

			return CalendarDataBookingInfoResponse::fromEntity($booking);
		}
		catch (Exception $e)
		{
			$this->addError(ErrorBuilder::buildFromException($e));

			return null;
		}
	}
}
