<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity\Booking;

use Bitrix\Booking\Entity\BaseEntityCollection;

/**
 * @method \Bitrix\Booking\Entity\Booking\ExternalDataItem|null getFirstCollectionItem()
 * @method ExternalDataCollection[] getIterator()
 */
class ExternalDataCollection extends BaseEntityCollection
{
	public function __construct(ExternalDataItem ...$items)
	{
		foreach ($items as $item)
		{
			$this->collectionItems[] = $item;
		}
	}

	public static function mapFromArray(array $props): self
	{
		$externalDataItems = array_map(
			static function ($item)
			{
				return ExternalDataItem::mapFromArray($item);
			},
			$props
		);

		return new ExternalDataCollection(...$externalDataItems);
	}

	public function diff(ExternalDataCollection $collectionToCompare): ExternalDataCollection
	{
		return new ExternalDataCollection(...$this->baseDiff($collectionToCompare));
	}
}
