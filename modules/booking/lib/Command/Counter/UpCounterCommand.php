<?php

declare(strict_types=1);

namespace Bitrix\Booking\Command\Counter;

use Bitrix\Booking\Command\AbstractCommand;
use Bitrix\Booking\Internals\Exception\ErrorBuilder;
use Bitrix\Booking\Internals\Exception\Exception;
use Bitrix\Booking\Internals\Service\CounterDictionary;
use Bitrix\Main\Result;

class UpCounterCommand extends AbstractCommand
{
	public function __construct(
		public readonly int $entityId,
		public readonly CounterDictionary $type,
	)
	{
	}

	public function toArray(): array
	{
		return [
			'entityId' => $this->entityId,
			'type'     => $this->type->value,
		];
	}

	protected function execute(): Result
	{
		try
		{
			(new UpCounterCommandHandler())($this);

			return new Result();
		}
		catch (Exception $bookingException)
		{
			return (new Result())->addError(ErrorBuilder::buildFromException($bookingException));
		}
	}
}
