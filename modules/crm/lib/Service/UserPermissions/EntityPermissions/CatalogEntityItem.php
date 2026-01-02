<?php

namespace Bitrix\Crm\Service\UserPermissions\EntityPermissions;

use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Crm\Service\UserPermissions\Helper\Shipment;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Loader;

/**
 * @internal
 * Do not use directly, only through \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->entityType() or getUserPermissions()->item()
 */

final class CatalogEntityItem
{
	private const CATALOG_ENTITIES = [\CCrmOwnerType::ShipmentDocument, \CCrmOwnerType::StoreDocument];
	private ?AccessController $catalogAccessController = null;

	public static function isCatalogEntity(int $entityTypeId): bool
	{
		return in_array($entityTypeId, self::CATALOG_ENTITIES, true);
	}

	public function __construct(int $userId)
	{
		$this->catalogAccessController = Loader::includeModule('catalog')
			? AccessController::getInstance($userId)
			: null
		;
	}

	public function canAddItems(Type $entityType, int $entityTypeId): bool
	{
		return $this->canDoOperationForItems(
			$entityType,
			$entityTypeId,
			ActionDictionary::ACTION_STORE_DOCUMENT_MODIFY,
			ActionDictionary::ACTION_STORE_VIEW,
			UserPermissions::OPERATION_ADD
		);
	}

	public function canRead(Item $item, int $entityTypeId, int $id): bool
	{
		return $this->canDoOperationForItem(
			$item,
			$entityTypeId,
			$id,
			ActionDictionary::ACTION_STORE_DOCUMENT_VIEW,
			ActionDictionary::ACTION_CATALOG_READ,
			UserPermissions::OPERATION_READ
		);
	}

	public function canReadItems(Type $entityType, int $entityTypeId): bool
	{
		return $this->canDoOperationForItems(
			$entityType,
			$entityTypeId,
			ActionDictionary::ACTION_STORE_DOCUMENT_VIEW,
			ActionDictionary::ACTION_CATALOG_READ,
			UserPermissions::OPERATION_READ
		);
	}

	public function canUpdate(Item $item, int $entityTypeId, int $id): bool
	{
		return $this->canDoOperationForItem(
			$item,
			$entityTypeId,
			$id,
			ActionDictionary::ACTION_STORE_DOCUMENT_MODIFY,
			ActionDictionary::ACTION_STORE_VIEW,
			UserPermissions::OPERATION_UPDATE
		);
	}

	public function canUpdateItems(Type $entityType, int $entityTypeId): bool
	{
		return $this->canDoOperationForItems(
			$entityType,
			$entityTypeId,
			ActionDictionary::ACTION_STORE_DOCUMENT_MODIFY,
			ActionDictionary::ACTION_STORE_VIEW,
			UserPermissions::OPERATION_UPDATE
		);
	}

	public function canDelete(Item $item, int $entityTypeId, int $id): bool
	{
		return $this->canDoOperationForItem(
			$item,
			$entityTypeId,
			$id,
			ActionDictionary::ACTION_STORE_DOCUMENT_DELETE,
			ActionDictionary::ACTION_STORE_VIEW,
			UserPermissions::OPERATION_DELETE
		);
	}

	public function canDeleteItems(Type $entityType, int $entityTypeId): bool
	{
		return $this->canDoOperationForItems(
			$entityType,
			$entityTypeId,
			ActionDictionary::ACTION_STORE_DOCUMENT_DELETE,
			ActionDictionary::ACTION_STORE_VIEW,
			UserPermissions::OPERATION_DELETE
		);
	}

	private function canDoOperationForItem(
		Item $item,
		int $entityTypeId,
		int $id,
		string $shipmentOperation,
		string $storeOperation,
		string $orderOperation
	): bool
	{
		if (!self::isCatalogEntity($entityTypeId))
		{
			throw new ArgumentOutOfRangeException('entityTypeId', self::CATALOG_ENTITIES);
		}

		if ($entityTypeId === \CCrmOwnerType::ShipmentDocument)
		{
			if ($this->catalogAccessController)
			{
				return
					$this->catalogAccessController->check(ActionDictionary::ACTION_CATALOG_READ)
					&& $this->catalogAccessController->check(ActionDictionary::ACTION_INVENTORY_MANAGEMENT_ACCESS)
					&& $this->catalogAccessController->checkByValue(
						$shipmentOperation,
						\Bitrix\Catalog\StoreDocumentTable::TYPE_SALES_ORDERS
					)
				;
			}

			$orderId = Shipment::getOrderIdByShipmentId($id);
			if ($orderId <= 0)
			{
				return false;
			}

			return match ($orderOperation) {
			 	UserPermissions::OPERATION_READ => $item->canRead(\CCrmOwnerType::Order, $orderId),
			 	UserPermissions::OPERATION_UPDATE => $item->canUpdate(\CCrmOwnerType::Order, $orderId),
			 	UserPermissions::OPERATION_DELETE => $item->canDelete(\CCrmOwnerType::Order, $orderId),
				default => false
			};
		}
		elseif ($entityTypeId === \CCrmOwnerType::StoreDocument)
		{
			return $this->catalogAccessController?->check($storeOperation);
		}

		return false;
	}

	private function canDoOperationForItems(
		Type $entityType,
		int $entityTypeId,
		string $shipmentOperation,
		string $storeOperation,
		string $orderOperation
	): bool
	{
		if (!self::isCatalogEntity($entityTypeId))
		{
			throw new ArgumentOutOfRangeException('entityTypeId', self::CATALOG_ENTITIES);
		}

		if ($entityTypeId === \CCrmOwnerType::ShipmentDocument && $this->catalogAccessController)
		{
			return
				$this->catalogAccessController->check(ActionDictionary::ACTION_CATALOG_READ)
				&& $this->catalogAccessController->check(ActionDictionary::ACTION_INVENTORY_MANAGEMENT_ACCESS)
				&& $this->catalogAccessController->checkByValue(
					$shipmentOperation,
					\Bitrix\Catalog\StoreDocumentTable::TYPE_SALES_ORDERS
				)
			;
		}
		elseif ($entityTypeId === \CCrmOwnerType::StoreDocument && $this->catalogAccessController)
		{
			return $this->catalogAccessController?->check($storeOperation);
		}

		return match ($orderOperation) {
			UserPermissions::OPERATION_ADD => $entityType->canAddItems(\CCrmOwnerType::Order),
			UserPermissions::OPERATION_READ => $entityType->canReadItems(\CCrmOwnerType::Order),
			UserPermissions::OPERATION_UPDATE => $entityType->canUpdateItems(\CCrmOwnerType::Order),
			UserPermissions::OPERATION_DELETE => $entityType->canDeleteItems(\CCrmOwnerType::Order),
			default => false
		};
	}
}
