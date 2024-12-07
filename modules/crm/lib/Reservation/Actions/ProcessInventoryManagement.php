<?php

namespace Bitrix\Crm\Reservation\Actions;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Catalog;
use Bitrix\Sale;

abstract class ProcessInventoryManagement extends Base
{
	public function processInternal(Crm\Item $item): Main\Result
	{
		$result = new Main\Result();

		$semanticId =
			$item->hasField(Crm\Item::FIELD_NAME_STAGE_SEMANTIC_ID)
				? $item->getStageSemanticId()
				: null
		;

		$processInventoryManagementResult = null;

		if ($semanticId === Crm\PhaseSemantics::SUCCESS)
		{
			$processInventoryManagementResult = $this->ship($item);
			if ($processInventoryManagementResult->isSuccess())
			{
				$processInventoryManagementResult = $this->unReserve($item);
			}
		}
		elseif ($semanticId === Crm\PhaseSemantics::FAILURE)
		{
			$processInventoryManagementResult = $this->unReserve($item);
		}

		if ($processInventoryManagementResult && !$processInventoryManagementResult->isSuccess())
		{
			Crm\Activity\Provider\StoreDocument::addProductActivity($item->getId());
		}

		return $result;
	}

	private function ship(Crm\Item $item): Main\Result
	{
		$entityBuilder = new Crm\Reservation\Entity\EntityBuilder();
		$entityBuilder
			->setOwnerTypeId($item->getEntityTypeId())
			->setOwnerId($item->getId())
		;

		$productRows = [];

		$itemProductRows = $item->getProductRows();
		if ($itemProductRows)
		{
			/** @var Crm\ProductRow $productRow */
			foreach ($itemProductRows as $productRow)
			{
				// If the product exists, but it is not in the catalog, shipment is not possible and is not required
				if ($productRow->getProductId() <= 0)
				{
					continue;
				}

				$productRows[$productRow->getId()] = $productRow->toArray();
			}
		}

		$basketReservation = new Crm\Reservation\BasketReservation();
		$basketReservation->addProducts($productRows);
		$reservationMap = $basketReservation->getReservationMap();

		$defaultStore = Catalog\StoreTable::getDefaultStoreId();
		foreach ($productRows as $product)
		{
			$storeId = (int)$product['STORE_ID'] > 0 ? (int)$product['STORE_ID'] : $defaultStore;

			$xmlId = null;
			if (isset($reservationMap[$product['ID']]))
			{
				$basketReservationData = Sale\Reservation\Internals\BasketReservationTable::getById(
					$reservationMap[$product['ID']]
				)->fetch();
				if ($basketReservationData)
				{
					$basketItem = Sale\Repository\BasketItemRepository::getInstance()->getById(
						$basketReservationData['BASKET_ID']
					);
					if ($basketItem)
					{
						$xmlId = $basketItem->getField('XML_ID');
					}
				}
			}

			$entityBuilder->addProduct(
				new Crm\Reservation\Product($product['ID'], $product['QUANTITY'], $storeId, $xmlId)
			);
		}

		$entity = $entityBuilder->build();

		return (new Crm\Reservation\Manager($entity))->ship();
	}

	private function unReserve(Crm\Item $item): Main\Result
	{
		$entityBuilder = new Crm\Reservation\Entity\EntityBuilder();
		$entityBuilder
			->setOwnerTypeId($item->getEntityTypeId())
			->setOwnerId($item->getId())
		;

		$entity = $entityBuilder->build();

		return (new Crm\Reservation\Manager($entity))->unReserve();
	}
}
