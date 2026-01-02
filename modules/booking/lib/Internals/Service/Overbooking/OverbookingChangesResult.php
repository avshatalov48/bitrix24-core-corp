<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Overbooking;

use Bitrix\Booking\Entity\Booking\BookingCollection;
use Bitrix\Main\Result;

class OverbookingChangesResult extends Result
{
	public function __construct(
		public readonly BookingCollection $notOverbooked,
		public readonly BookingCollection $overbooked,
	)
	{
		parent::__construct();
	}

	public static function buildEmpty(): self
	{
		return new self(
			new BookingCollection(),
			new BookingCollection(),
		);
	}
}
