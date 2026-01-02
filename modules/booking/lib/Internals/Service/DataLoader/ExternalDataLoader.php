<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\DataLoader;

use Bitrix\Booking\Entity\BaseEntityCollection;
use Bitrix\Booking\Entity\ExternalData\ExternalDataCollection;
use Bitrix\Booking\Entity\ExternalData\ExternalDataItem;

class ExternalDataLoader implements DataLoaderInterface
{
	public function __construct(
		private DataLoaderFactory|null $dataLoaderFactory = null,
	)
	{
		$this->dataLoaderFactory = $dataLoaderFactory ?? new DataLoaderFactory();
	}

	/**
	 * @param ExternalDataCollection $collection
	 */
	public function loadForCollection(BaseEntityCollection $collection): void
	{
		if ($collection->isEmpty())
		{
			return;
		}

		$types = $this->groupItemsByType($collection);
		$this->loadByTypes($types);
	}

	/**
	 * @param ExternalDataCollection ...$collections
	 */
	public function loadForCollections(BaseEntityCollection ...$collections): void
	{
		$aggregatedTypes = new ExternalDataCollection();

		foreach ($collections as $collection)
		{
			if ($collection->isEmpty())
			{
				continue;
			}

			$this->aggregateCollectionItems($collection, $aggregatedTypes);
		}

		$types = $this->groupItemsByType($aggregatedTypes);
		$this->loadByTypes($types);
	}

	/**
	 * @param ExternalDataCollection $collection
	 */
	private function groupItemsByType(BaseEntityCollection $collection): array
	{
		$types = [];
		$processedTypes = [];

		/** @var ExternalDataItem $externalDataItem */
		foreach ($collection as $externalDataItem)
		{
			$type = $this->dataLoaderFactory->getTypeByModuleAndEntityType(
				$externalDataItem->getModuleId(),
				$externalDataItem->getEntityTypeId(),
			);

			if (
				!$type
				|| isset($processedTypes[$type])
				|| !$this->dataLoaderFactory->getByType($type)
			)
			{
				continue;
			}

			$processedTypes[$type] = true;
			$filteredItems = $collection->filterByType((new $type())->buildFilter());

			if (!$filteredItems->isEmpty())
			{
				$types[$type] = $filteredItems;
			}
		}

		return $types;
	}

	private function aggregateCollectionItems(
		BaseEntityCollection $collection,
		BaseEntityCollection $aggregated,
	): void
	{
		foreach ($collection as $externalDataItem)
		{
			$aggregated->add($externalDataItem);
		}
	}

	private function loadByTypes(array $types): void
	{
		foreach ($types as $type => $items)
		{
			// provider IS defined, cause earlier we checked if it exists
			$provider = $this->dataLoaderFactory->getByType($type);
			$provider?->loadForCollection($items);
		}
	}
}
