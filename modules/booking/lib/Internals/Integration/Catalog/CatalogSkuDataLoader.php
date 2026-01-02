<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Integration\Catalog;

use Bitrix\Booking\Entity\BaseEntityCollection;
use Bitrix\Booking\Entity\ExternalData\ExternalDataCollection;
use Bitrix\Booking\Entity\ExternalData\ItemType\CatalogSkuItemType;
use Bitrix\Booking\Internals\Integration\Catalog\ExternalData\ExternalDataSkuDto;
use Bitrix\Booking\Internals\Service\DataLoader\DataLoaderInterface;
use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;

class CatalogSkuDataLoader implements DataLoaderInterface
{
	public function __construct(
		private readonly ServiceSkuProvider $serviceSkuProvider,
		private CurrentUser|null $currentUser = null,
	)
	{
		$this->currentUser = $currentUser ?? CurrentUser::get();
	}

	/**
	 * @param ExternalDataCollection $collection
	 */
	public function loadForCollection(BaseEntityCollection $collection): void
	{
		if (!$this->isAvailable())
		{
			return;
		}

		$filteredCollection = $this->getRelevantItems($collection);

		if ($filteredCollection->isEmpty())
		{
			return;
		}

		if (!$this->checkCatalogReadAccess())
		{
			$this->setEmptyData($filteredCollection);

			return;
		}

		$this->loadAndTransformData($filteredCollection);
	}

	private function isAvailable(): bool
	{
		return Loader::includeModule('catalog');
	}

	/**
	 * @param ExternalDataCollection $collection
	 */
	private function getRelevantItems(BaseEntityCollection $collection): BaseEntityCollection
	{
		return $collection->filterByType((new CatalogSkuItemType())->buildFilter());
	}

	/**
	 * @param ExternalDataCollection $collection
	 */
	private function extractEntityIds(BaseEntityCollection $collection): array
	{
		return array_values(
			array_unique(
				array_map('intval', $collection->getValues())
			)
		);
	}

	/**
	 * @param ExternalDataCollection $collection
	 */
	private function setEmptyData(BaseEntityCollection $collection): void
	{
		foreach ($collection as $externalDataItem)
		{
			$externalDataItem->setData(ExternalDataSkuDto::buildEmpty((int)$externalDataItem->getValue())->toArray());
		}
	}

	/**
	 * @param ExternalDataCollection $collection
	 */
	private function loadAndTransformData(BaseEntityCollection $collection): void
	{
		$entityIds = $this->extractEntityIds($collection);
		$services = $this->serviceSkuProvider->get($entityIds);

		if (!$services)
		{
			return;
		}

		$indexedServices = $this->indexServicesByIds($services);

		foreach ($collection as $externalDataItem)
		{
			$skuId = (int)$externalDataItem->getValue();

			if (!isset($indexedServices[$skuId]))
			{
				continue;
			}

			$externalDataItem->setData(ExternalDataSkuDto::fromSku($indexedServices[$skuId])->toArray());
		}
	}

	/**
	 * @param Sku[] $services
	 */
	private function indexServicesByIds(array $services): array
	{
		$indexed = [];
		foreach ($services as $service)
		{
			$indexed[$service->getId()] = $service;
		}

		return $indexed;
	}

	private function checkCatalogReadAccess(): bool
	{
		return AccessController::getInstance((int)$this->currentUser->getId())
			->check(ActionDictionary::ACTION_CATALOG_READ)
		;
	}
}
