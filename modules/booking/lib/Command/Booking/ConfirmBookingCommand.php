<?php

declare(strict_types=1);

namespace Bitrix\Booking\Command\Booking;

use Bitrix\Booking\Command\AbstractCommand;
use Bitrix\Booking\Internals\Exception\ErrorBuilder;
use Bitrix\Booking\Internals\Exception\Exception;
use Bitrix\Main\Result;

class ConfirmBookingCommand extends AbstractCommand
{
	public function __construct(
		public readonly string $hash,
	)
	{
	}

	public function toArray(): array
	{
		return [
			'hash' => $this->hash,
		];
	}

	public static function mapFromArray(array $props): self
	{
		return new self(
			hash: $props['hash'],
		);
	}

	protected function execute(): Result
	{
		try
		{
			return new BookingResult(
				(new ConfirmBookingCommandHandler())($this),
			);
		}
		catch (Exception $bookingException)
		{
			return (new Result())->addError(ErrorBuilder::buildFromException($bookingException));
		}
	}
}
