<?php

declare(strict_types=1);

namespace Bitrix\Booking\Command\Booking;

use Bitrix\Booking\Command\AbstractCommand;
use Bitrix\Booking\Entity;
use Bitrix\Booking\Internals\Exception\ErrorBuilder;
use Bitrix\Booking\Internals\Exception\Exception;
use Bitrix\Main\Result;

class AddBookingCommand extends AbstractCommand
{
	public function __construct(
		public readonly int $createdBy,
		public readonly Entity\Booking\Booking $booking,
		public readonly bool $allowOverbooking = false,
	)
	{
	}

	public function toArray(): array
	{
		return [
			'createdBy' => $this->createdBy,
			'booking' => $this->booking->toArray(),
			'allowOverbooking' => $this->allowOverbooking,
		];
	}

	public static function mapFromArray(array $props): self
	{
		return new self(
			createdBy: $props['createdBy'],
			booking: Entity\Booking\Booking::mapFromArray($props['booking']),
			allowOverbooking: isset($props['allowOverbooking']) && $props['allowOverbooking'],
		);
	}

	protected function execute(): Result
	{
		try
		{
			return new BookingResult(
				booking: (new AddBookingCommandHandler())($this),
			);
		}
		catch (Exception $bookingException)
		{
			return (new Result())->addError(ErrorBuilder::buildFromException($bookingException));
		}
	}
}
