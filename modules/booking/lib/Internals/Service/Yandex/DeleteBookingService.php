<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Yandex;

use Bitrix\Booking\Command\Booking\RemoveBookingCommand;
use Bitrix\Booking\Entity\Booking\BookingSource;
use Bitrix\Booking\Internals\Exception\Yandex\BookingCancelForbidden;
use Bitrix\Booking\Internals\Exception\Yandex\BookingNotFoundException;
use Bitrix\Booking\Internals\Repository\BookingRepositoryInterface;
use Bitrix\Booking\Provider\Params\Booking\BookingFilter;
use Bitrix\Main\Engine\CurrentUser;

class DeleteBookingService
{
	private BookingRepositoryInterface $bookingRepository;

	public function __construct(
		BookingRepositoryInterface $bookingRepository,
	)
	{
		$this->bookingRepository = $bookingRepository;
	}

	public function delete(string $id): void
	{
		$id = (int)$id;

		$booking = $this->bookingRepository->getList(
			filter: new BookingFilter([
				'ID' => $id,
				'SOURCE' => BookingSource::Yandex->value,
				'INCLUDE_DELETED' => true,
			]),
		)->getFirstCollectionItem();

		if (!$booking)
		{
			throw new BookingNotFoundException();
		}

		if ($booking->isDeleted())
		{
			return;
		}

		$command = new RemoveBookingCommand(
			id: $id,
			removedBy: (int)CurrentUser::get()->getId(),
		);

		$result = $command->run();
		if (!$result->isSuccess())
		{
			throw new BookingCancelForbidden();
		}
	}
}
