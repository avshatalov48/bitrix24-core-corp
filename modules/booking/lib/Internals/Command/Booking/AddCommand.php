<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Command\Booking;

use Bitrix\Booking\Entity;
use Bitrix\Booking\Internals\Command\CommandInterface;

class AddCommand implements CommandInterface
{
	public function __construct(
		public readonly int $createdBy,
		public readonly Entity\Booking\Booking $booking,
	)
	{

	}

	public function toArray(): array
	{
		return [
			'createdBy' => $this->createdBy,
			'booking' => $this->booking->toArray(),
		];
	}

	public static function mapFromArray(array $props): self
	{
		return new self(
			createdBy: $props['createdBy'],
			booking: Entity\Booking\Booking::mapFromArray($props['booking']),
		);
	}
}
