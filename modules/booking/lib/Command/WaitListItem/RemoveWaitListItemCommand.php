<?php

namespace Bitrix\Booking\Command\WaitListItem;

use Bitrix\Booking\Command\AbstractCommand;
use Bitrix\Booking\Internals\Exception\Exception;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

class RemoveWaitListItemCommand extends AbstractCommand
{
	public function __construct(
		public readonly int $id,
		public readonly int $removedBy,
	)
	{

	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'removedBy' => $this->removedBy,
		];
	}

	public static function mapFromArray(array $props): self
	{
		return new self(
			id: $props['id'],
			removedBy: $props['removedBy'],
		);
	}

	protected function execute(): Result
	{
		try
		{
			(new RemoveWaitListItemCommandHandler())($this);

			return new Result();
		}
		catch (Exception $bookingException)
		{
			return (new Result())->addError(
				new Error(
					$bookingException->getMessage(),
					$bookingException->getCode(),
					[
						'isPublic' => $bookingException->isPublic(),
						'id' => $this->id,
					]
				)
			);
		}
	}
}
