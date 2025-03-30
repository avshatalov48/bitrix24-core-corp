<?php

declare(strict_types=1);

namespace Bitrix\Booking\Command\Booking;

use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Main\Result;

class BookingResult extends Result
{
	public function __construct(private Booking $booking)
	{
		parent::__construct();
	}

	public function getBooking(): Booking
	{
		return $this->booking;
	}
}
