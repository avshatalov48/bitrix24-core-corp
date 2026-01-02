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
			'updatedBy' => $this->updatedBy,
			'resourceType' => $this->resourceType->toArray(),
			'rangeCollection' => $this->rangeCollection?->toArray(),
		];
	}

	public static function mapFromArray(array $props): self
	{
		$rangeCollection = isset($props['rangeCollection'])
			? Entity\Slot\RangeCollection::mapFromArray($props['rangeCollection'])
			: null
		;

		return new self(
			updatedBy: $props['updatedBy'],
			resourceType: Entity\ResourceType\ResourceType::mapFromArray($props['resourceType']),
			rangeCollection: $rangeCollection,
		);
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
