<?php

declare(strict_types=1);

namespace Bitrix\Booking\Command\ResourceType;

use Bitrix\Booking\Command\AbstractCommand;
use Bitrix\Booking\Entity;
use Bitrix\Booking\Internals\Exception\ErrorBuilder;
use Bitrix\Booking\Internals\Exception\Exception;
use Bitrix\Main\Result;

class UpdateResourceTypeCommand extends AbstractCommand
{
	public function __construct(
		public readonly int $updatedBy,
		public readonly Entity\ResourceType\ResourceType $resourceType,
		public readonly Entity\Slot\RangeCollection|null $rangeCollection,
	)
	{
	}

	public function toArray(): array
	{
		return [
			'resourceType' => $this->resourceType->toArray(),
			'updatedBy' => $this->updatedBy,
			'ranges' => $this->rangeCollection?->toArray(),
		];
	}

	protected function execute(): Result
	{
		try
		{
			return new ResourceTypeResult(
				(new UpdateResourceTypeCommandHandler())($this),
			);
		}
		catch (Exception $bookingException)
		{
			return (new Result())->addError(ErrorBuilder::buildFromException($bookingException));
		}
	}
}
