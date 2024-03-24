<?php

namespace Bitrix\Crm\Reservation\Component;

use Bitrix\Crm\Order\OrderDealSynchronizer\Products\BasketXmlId;
use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Catalog;
use Bitrix\Crm\Service\Sale\BasketService;
use Bitrix\Sale;

Main\Localization\Loc::loadLanguageFile(__FILE__);

final class InventoryManagement
{
	private Crm\Item $itemBeforeSave;
	private Crm\Item $itemAfterSave;
	private Crm\Service\Factory $factory;

	/**
	 * @param Crm\Item $itemBeforeSave
	 * @param Crm\Item $itemAfterSave
	 */
	public function __construct(Crm\Item $itemBeforeSave, Crm\Item $itemAfterSave)
	{
		$this->itemBeforeSave = $itemBeforeSave;
		$this->itemAfterSave = $itemAfterSave;

		$this->factory = Crm\Service\Container::getInstance()->getFactory($this->itemAfterSave->getEntityTypeId());
	}

	/**
	 * @return Main\Result
	 */
	public function process(): Main\Result
	{
		$result = new Main\Result();

		if (!$this->isProcessInventoryManagementAvailable())
		{
			return $result;
		}

		if ($this->itemBeforeSave->isNew())
		{
			$result = $this->processOnAdd();
		}
		else
		{
			$result = $this->processOnUpdate();
		}

		return $result;
	}

	private function processOnAdd(): Main\Result
	{
		$result = new Main\Result();

		$semanticId =
			$this->itemAfterSave->hasField(Crm\Item::FIELD_NAME_STAGE_SEMANTIC_ID)
				? $this->itemAfterSave->getStageSemanticId()
				: null
		;

		if ($semanticId && Crm\PhaseSemantics::isFinal($semanticId))
		{
			$result = $this->processInternal();
		}

		return $result;
	}

	private function processOnUpdate(): Main\Result
	{
		$result = new Main\Result();

		$previousStageId =
			$this->itemBeforeSave->hasField(Crm\Item::FIELD_NAME_STAGE_ID)
				? $this->itemBeforeSave->getStageId()
				: null
		;

		$currentStageId =
			$this->itemAfterSave->hasField(Crm\Item::FIELD_NAME_STAGE_ID)
				? $this->itemAfterSave->getStageId()
				: null
		;

		$isMovedToFinalStage =
			isset($previousStageId, $currentStageId)
			&& Crm\Comparer\ComparerBase::isMovedToFinalStage(\CCrmOwnerType::Deal, $previousStageId, $currentStageId)
		;
		if ($isMovedToFinalStage)
		{
			$result = $this->processInternal();
		}

		return $result;
	}

	private function processInternal(): Main\Result
	{
		$result = new Main\Result();

		$semanticId =
			$this->itemAfterSave->hasField(Crm\Item::FIELD_NAME_STAGE_SEMANTIC_ID)
				? $this->itemAfterSave->getStageSemanticId()
				: null
		;

		$processInventoryManagementResult = null;
		if ($semanticId === Crm\PhaseSemantics::SUCCESS)
		{
			$processInventoryManagementResult = $this->ship();
			if ($processInventoryManagementResult->isSuccess())
			{
				$processInventoryManagementResult = $this->unReserve();
			}
		}
		elseif ($semanticId === Crm\PhaseSemantics::FAILURE)
		{
			$processInventoryManagementResult = $this->unReserve();
		}

		if ($processInventoryManagementResult && !$processInventoryManagementResult->isSuccess())
		{
			Crm\Activity\Provider\StoreDocument::addProductActivity($this->itemAfterSave->getId());

			$result->addError(Crm\Reservation\Error\InventoryManagementError::create());
		}

		return $result;
	}

	private function ship(): Main\Result
	{
		$entityBuilder = new Crm\Reservation\Entity\EntityBuilder();
		$entityBuilder->setOwnerTypeId($this->itemAfterSave->getEntityTypeId());
		$entityBuilder->setOwnerId($this->itemAfterSave->getId());

		$entityProducts = $this->getEntityProducts();
		$entityProductsToBasketItems = BasketService::getInstance()->getRowIdsToBasketIdsByEntity(
			$this->itemAfterSave->getEntityTypeId(),
			$this->itemAfterSave->getId()
		);

		$basketReservation = new Crm\Reservation\BasketReservation();
		$basketReservation->addProducts($entityProducts);
		$reservationMap = $basketReservation->getReservationMap();

		$defaultStore = Catalog\StoreTable::getDefaultStoreId();
		foreach ($entityProducts as $product)
		{
			$storeId = (int)$product['STORE_ID'] > 0 ? (int)$product['STORE_ID'] : $defaultStore;

			$basketItemId = null;
			if (isset($reservationMap[$product['ID']]))
			{
				$basketReservationData = Sale\Reservation\Internals\BasketReservationTable::getById(
					$reservationMap[$product['ID']]
				)->fetch();
				if ($basketReservationData)
				{
					$basketItemId = $basketReservationData['BASKET_ID'];
				}
			}

			if (!$basketItemId && isset($entityProductsToBasketItems[$product['ID']]))
			{
				$basketItemId = $entityProductsToBasketItems[$product['ID']];
			}

			$xmlId = null;
			if ($basketItemId)
			{
				$basketItem = Sale\Repository\BasketItemRepository::getInstance()->getById($basketItemId);
				if ($basketItem)
				{
					$xmlId = $basketItem->getField('XML_ID');
				}
			}
			if (!$xmlId)
			{
				$xmlId =  BasketXmlId::getXmlIdFromRowId((int)$product['ID']);
			}

			$entityBuilder->addProduct(
				new Crm\Reservation\Product($product['ID'], $product['QUANTITY'], $storeId, $xmlId)
			);
		}

		$entity = $entityBuilder->build();

		return (new Crm\Reservation\Manager($entity))->ship();
	}

	private function unReserve(): Main\Result
	{
		$entityBuilder = new Crm\Reservation\Entity\EntityBuilder();
		$entityBuilder
			->setOwnerTypeId($this->itemAfterSave->getEntityTypeId())
			->setOwnerId($this->itemAfterSave->getId())
		;

		$entity = $entityBuilder->build();

		return (new Crm\Reservation\Manager($entity))->unReserve();
	}

	private function getEntityProducts(): array
	{
		static $entityProducts = [];

		if ($entityProducts)
		{
			return $entityProducts;
		}

		$productRows = $this->itemAfterSave->getProductRows();
		/** @var Crm\ProductRow $productRow */
		foreach ($productRows as $productRow)
		{
			$entityProduct = $productRow->toArray();

			$productReservation = $productRow->getProductRowReservation();
			if ($productReservation)
			{
				$entityProduct += $productReservation->toArray();
			}

			$entityProducts[] = $entityProduct;
		}

		return $entityProducts;
	}

	private function isProcessInventoryManagementAvailable(): bool
	{
		return $this->factory->isInventoryManagementEnabled();
	}
}
