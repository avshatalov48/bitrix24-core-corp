<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Yandex;

use Bitrix\Booking\Entity\Booking\BookingSource;
use Bitrix\Booking\Internals\Exception\Yandex\BookingNotFoundException;
use Bitrix\Booking\Internals\Repository\BookingRepositoryInterface;
use Bitrix\Booking\Internals\Service\Yandex\Dto\Item\Booking;
use Bitrix\Booking\Provider\Params\Booking\BookingFilter;
use Bitrix\Booking\Provider\Params\Booking\BookingSelect;

class BookingProvider
{
	private BookingRepositoryInterface $bookingRepository;

	public function __construct(BookingRepositoryInterface $bookingRepository)
	{
		$this->bookingRepository = $bookingRepository;
	}

	public function getById(string $id): Booking
	{
		$id = (int)$id;

		$booking = $this->bookingRepository->getList(
			filter: new BookingFilter([
				'ID' => $id,
				'SOURCE' => BookingSource::Yandex->value,
				'INCLUDE_DELETED' => true,
			]),
			select: (new BookingSelect([
				'RESOURCES',
				'EXTERNAL_DATA',
			]))->prepareSelect(),
		)->getFirstCollectionItem();

		if (!$booking)
		{
			throw new BookingNotFoundException();
		}

		return Booking::createFromBooking($booking);
	}
}
