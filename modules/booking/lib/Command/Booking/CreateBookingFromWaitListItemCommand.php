<?php

declare(strict_types=1);

namespace Bitrix\Booking\Command\Booking;

use Bitrix\Booking\Command\AbstractCommand;
use Bitrix\Booking\Internals\Exception\ErrorBuilder;
use Bitrix\Booking\Internals\Exception\Exception;
use Bitrix\Main\Result;

/** @method BookingResult run() */
class CreateBookingFromWaitListItemCommand extends AbstractCommand
{
	public function __construct(
		public readonly int $waitListItemId,
		public readonly array $resources,
		public readonly array $datePeriod,
		public readonly int $createdBy,
		public readonly string|null $name = null,
		public readonly bool $allowOverbooking = false,
	)
	{
	}

	public function toArray(): array
	{
		return [
			'waitListItemId' => $this->waitListItemId,
			'resources' => $this->resources,
			'datePeriod' => $this->datePeriod,
			'createdBy' => $this->createdBy,
			'name' => $this->name,
			'allowOverbooking' => $this->allowOverbooking,
		];
	}

	protected function execute(): Result
	{
		try
		{
			return new BookingResult(
				booking: (new CreateBookingFromWaitListItemCommandHandler())($this),
			);
		}
		catch (Exception $bookingException)
		{
			return (new Result())->addError(ErrorBuilder::buildFromException($bookingException));
		}
	}
}
