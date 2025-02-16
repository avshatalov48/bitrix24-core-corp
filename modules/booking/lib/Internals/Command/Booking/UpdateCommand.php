<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Command\Booking;

use Bitrix\Booking\Entity;
use Bitrix\Booking\Internals\Command\CommandInterface;

class UpdateCommand implements CommandInterface
{
	public function __construct(
		public readonly int $updatedBy,
		public readonly Entity\Booking\Booking $booking,
	)
	{

	}

	public function toArray(): array
	{
		return [
			'booking' => $this->booking->toArray(),
			'updatedBy' => $this->updatedBy,
		];
	}

	public static function mapFromArray(array $props): self
	{
		return new self(
			updatedBy: $props['updatedBy'],
			booking: Entity\Booking\Booking::mapFromArray($props['booking']),
		);
	}
}
