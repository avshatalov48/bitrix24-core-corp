<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Overbooking;

use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Entity\Booking\BookingCollection;
use Bitrix\Booking\Entity\DatePeriod;
use Bitrix\Main\Result;

class IntersectionResult extends Result
{
	private BookingCollection $bookingCollection;

	public function __construct(BookingCollection $bookingCollection = null)
	{
		$this->bookingCollection = $bookingCollection ?? new BookingCollection();

		parent::__construct();
	}

	public function addBooking(Booking $booking): self
	{
		$this->bookingCollection->addUnique($booking);

		return $this;
	}

	public function getBookingCollection(): BookingCollection
	{
		return $this->bookingCollection;
	}

	public function setIsSuccess(bool $isSuccess): self
	{
		$this->isSuccess = $isSuccess;

		return $this;
	}

	public function hasIntersections(): bool
	{
		return !$this->bookingCollection->isEmpty();
	}
}
