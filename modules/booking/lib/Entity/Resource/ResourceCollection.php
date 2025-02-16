<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity\Resource;

use Bitrix\Booking\Entity\BaseEntityCollection;
use Bitrix\Booking\Entity\Slot\RangeCollection;
use DateTimeImmutable;

/**
 * @method \Bitrix\Booking\Entity\Resource\Resource|null getFirstCollectionItem()
 * @method Resource[] getIterator()
 */
class ResourceCollection extends BaseEntityCollection
{
	public function __construct(Resource ...$resources)
	{
		foreach ($resources as $resource)
		{
			$this->collectionItems[] = $resource;
		}
	}

	public static function mapFromArray(array $props): self
	{
		$resources = [];
		foreach ($props as $resource)
		{
			$resources[] = Resource::mapFromArray($resource);
		}

		return new ResourceCollection(...$resources);
	}

	public function diff(ResourceCollection $collectionToCompare): ResourceCollection
	{
		return new ResourceCollection(...$this->baseDiff($collectionToCompare));
	}

	public function mergeSlotRanges(DateTimeImmutable $date): RangeCollection
	{
		$result = null;

		/** @var Resource $resource */
		foreach ($this->collectionItems as $resource)
		{
			/** @var RangeCollection $slotRanges */
			$slotRanges = $resource->getSlotRanges();
			if ($slotRanges->isEmpty())
			{
				continue;
			}

			if ($result === null)
			{
				$result = $slotRanges;
			}
			else
			{
				$result = $slotRanges->merge($result, $date);
			}
		}

		return $result ?: new RangeCollection();
	}
}
