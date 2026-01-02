<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity\ExternalData;

use Bitrix\Booking\Entity\BaseEntityCollection;
use Bitrix\Booking\Entity\ExternalData\ItemType\ItemTypeFilter;

/**
 * @method ExternalDataItem|null getFirstCollectionItem()
 * @method \ArrayIterator<ExternalDataItem> getIterator()
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

	public function filterByType(ItemTypeFilter $filter, bool $exclude = false): ExternalDataCollection
	{
		$result = new ExternalDataCollection();

		/** @var ExternalDataItem $externalDataItem */
		foreach ($this as $externalDataItem)
		{
			$isOfTypeCondition = (
				$externalDataItem->getModuleId() === $filter->moduleId
				&& $externalDataItem->getEntityTypeId() === $filter->entityTypeId
			);
			$condition = $exclude ? !$isOfTypeCondition : $isOfTypeCondition;

			if ($condition)
			{
				$result->add($externalDataItem);
			}
		}

		return $result;
	}

	public function getValues(): array
	{
		$result = [];

		foreach ($this as $item)
		{
			$result[] = $item->getValue();
		}

		return $result;
	}
}
