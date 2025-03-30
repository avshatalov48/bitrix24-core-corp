<?php

declare(strict_types=1);

namespace Bitrix\Booking\Command\Resource;

use Bitrix\Booking\Command\AbstractCommand;
use Bitrix\Booking\Internals\Exception\ErrorBuilder;
use Bitrix\Booking\Internals\Exception\Exception;
use Bitrix\Main\Result;

class RemoveResourceCommand extends AbstractCommand
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

	protected function execute(): Result
	{
		try
		{
			(new RemoveResourceCommandHandler())($this);

			return new Result();
		}
		catch (Exception $bookingException)
		{
			return (new Result())->addError(ErrorBuilder::buildFromException($bookingException));
		}
	}
}
