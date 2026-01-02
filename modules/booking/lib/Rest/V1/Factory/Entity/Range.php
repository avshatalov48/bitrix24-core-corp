<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\Factory\Entity;

use Bitrix\Booking\Entity\Slot\RangeCollection;
use Bitrix\Booking\Internals\Exception\ErrorBuilder;
use Bitrix\Booking\Internals\Exception\Exception;
use Bitrix\Booking\Internals\Exception\InvalidArgumentException;
use Bitrix\Main\Result;
use Bitrix\Booking\Rest\V1\Factory\EntityFactory;

class Range extends EntityFactory
{
	public function validateRestFields(array $fields): Result
	{
		$validationResult = new Result();
		try
		{
			$this->createFromRestFields($fields);
		}
		catch (InvalidArgumentException)
		{
			$validationResult->addError(
				ErrorBuilder::build(
					message: 'Invalid range fields',
					code: Exception::CODE_INVALID_ARGUMENT,
				),
			);
		}

		return $validationResult;
	}

	public function createCollectionFromRestFields(
		array $items,
		?\Bitrix\Booking\Entity\Resource\Resource $resource = null
	): RangeCollection
	{
		$collection = new RangeCollection();

		foreach ($items as $item)
		{
			$range = $this->createFromRestFields($item);

			$range->setResourceId($resource?->getId());
			$range->setTypeId($resource?->getType()?->getId());

			$collection->add($range);
		}

		return $collection;
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public function createFromRestFields(array $fields): \Bitrix\Booking\Entity\Slot\Range
	{
		$range = new \Bitrix\Booking\Entity\Slot\Range();

		$range->setFrom((int)$fields['FROM']);
		$range->setTo((int)$fields['TO']);
		$range->setTimezone((string)$fields['TIMEZONE']);
		$range->setWeekDays((array)$fields['WEEK_DAYS']);
		$range->setSlotSize((int)$fields['SLOT_SIZE']);

		return $range;
	}
}
