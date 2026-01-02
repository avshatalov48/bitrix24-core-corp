<?php

namespace Bitrix\Booking\Command\WaitListItem;

use Bitrix\Booking\Command\AbstractCommand;
use Bitrix\Booking\Internals\Exception\ErrorBuilder;
use Bitrix\Booking\Internals\Exception\Exception;
use Bitrix\Main\Result;

/** @method WaitListItemResult run() */
class CreateWaitListItemFromBookingCommand extends AbstractCommand
{
	public function __construct(
		public readonly int $bookingId,
		public readonly int $createdBy
	)
	{
	}

	public function toArray(): array
	{
		return [
			'bookingId' => $this->bookingId,
			'createdBy' => $this->createdBy,
		];
	}

	public function mapFromArray(array $props): self
	{
		return new self(
			$props['bookingId'],
			$props['createdBy'],
		);
	}

	protected function execute(): Result
	{
		try
		{
			return new WaitListItemResult(
				waitListItem: (new CreateWaitListItemFromBookingCommandHandler())($this),
			);
		}
		catch (Exception $waitListItemException)
		{
			return (new Result())->addError(ErrorBuilder::buildFromException($waitListItemException));
		}
	}
}
