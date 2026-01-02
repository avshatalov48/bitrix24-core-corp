<?php

namespace Bitrix\Crm\Service\UserPermissions\EntityPermissions;

use Bitrix\Crm\Service\UserPermissions\Helper\Payment;
use Bitrix\Crm\Service\UserPermissions\Helper\Shipment;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Crm\Service\UserPermissions\Helper\Check;
use Bitrix\Crm\ItemIdentifier;

/**
 * @internal
 * Do not use directly, only through \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->saleEntityItem()
 */

final class SaleEntityItem
{
	private const SALE_ENTITIES = [\CCrmOwnerType::OrderShipment, \CCrmOwnerType::OrderPayment, \CCrmOwnerType::OrderCheck];

	public static function isSaleEntity(int $entityTypeId): bool
	{
		return in_array($entityTypeId, self::SALE_ENTITIES, true);
	}

	public function canAddItems(Type $entityType, int $entityTypeId): bool
	{
		if (!self::isSaleEntity($entityTypeId))
		{
			throw new ArgumentOutOfRangeException('entityTypeId', self::SALE_ENTITIES);
		}

		return
			\CCrmSaleHelper::isWithOrdersMode()
				? $entityType->canAddItems(\CCrmOwnerType::Order)
				: $entityType->canAddItems(\CCrmOwnerType::Deal)
		;
	}

	public function canUpdate(Item $item, Type $entityType, int $entityTypeId, int $id): bool
	{
		if (!self::isSaleEntity($entityTypeId))
		{
			throw new ArgumentOutOfRangeException('entityTypeId', self::SALE_ENTITIES);
		}

		if (\CCrmSaleHelper::isWithOrdersMode())
		{
			$orderId = $this->getOrderIdByEntityId($entityTypeId, $id);
			if ($orderId <= 0)
			{
				return false;
			}

			return $item->canUpdate(\CCrmOwnerType::Order, $orderId);
		}
		else
		{
			$identifier = $this->getBoundIdentifierByEntityId($entityTypeId, $id);
			if (!$identifier) // order without bindings
			{
				return $entityType->canUpdateItems(\CCrmOwnerType::Deal);
			}

			return $item->canUpdateItemIdentifier($identifier);
		}
	}

	public function canUpdateItems(Type $entityType, int $entityTypeId): bool
	{
		if (!self::isSaleEntity($entityTypeId))
		{
			throw new ArgumentOutOfRangeException('entityTypeId', self::SALE_ENTITIES);
		}

		return
			\CCrmSaleHelper::isWithOrdersMode()
				? $entityType->canUpdateItems(\CCrmOwnerType::Order)
				: $entityType->canUpdateItems(\CCrmOwnerType::Deal)
			;
	}

	public function canRead(Item $item, Type $entityType, int $entityTypeId, int $id): bool
	{
		if (!self::isSaleEntity($entityTypeId))
		{
			throw new ArgumentOutOfRangeException('entityTypeId', self::SALE_ENTITIES);
		}

		if (\CCrmSaleHelper::isWithOrdersMode())
		{
			$orderId = $this->getOrderIdByEntityId($entityTypeId, $id);
			if ($orderId <= 0)
			{
				return false;
			}

			return $item->canRead(\CCrmOwnerType::Order, $orderId);
		}
		else
		{
			$identifier = $this->getBoundIdentifierByEntityId($entityTypeId, $id);
			if (!$identifier) // order without bindings
			{
				return $entityType->canReadItems(\CCrmOwnerType::Deal);
			}

			return $item->canReadItemIdentifier($identifier);
		}
	}

	public function canReadItems(Type $entityType, int $entityTypeId): bool
	{
		if (!self::isSaleEntity($entityTypeId))
		{
			throw new ArgumentOutOfRangeException('entityTypeId', self::SALE_ENTITIES);
		}

		return \CCrmSaleHelper::isWithOrdersMode()
			? $entityType->canReadItems(\CCrmOwnerType::Order)
			: $entityType->canReadItems(\CCrmOwnerType::Deal)
		;
	}

	public function canDelete(Item $item, Type $entityType, int $entityTypeId, int $id): bool
	{
		if (!self::isSaleEntity($entityTypeId))
		{
			throw new ArgumentOutOfRangeException('entityTypeId', self::SALE_ENTITIES);
		}

		if (\CCrmSaleHelper::isWithOrdersMode())
		{
			$orderId = $this->getOrderIdByEntityId($entityTypeId, $id);
			if ($orderId <= 0)
			{
				return false;
			}

			return $item->canDelete(\CCrmOwnerType::Order, $orderId);
		}
		else
		{
			$identifier = $this->getBoundIdentifierByEntityId($entityTypeId, $id);
			if (!$identifier) // order without bindings
			{
				return $entityType->canDeleteItems(\CCrmOwnerType::Deal);
			}

			return $item->canDeleteItemIdentifier($identifier);
		}
	}

	public function canDeleteItems(Type $entityType, int $entityTypeId): bool
	{
		if (!self::isSaleEntity($entityTypeId))
		{
			throw new ArgumentOutOfRangeException('entityTypeId', self::SALE_ENTITIES);
		}

		return \CCrmSaleHelper::isWithOrdersMode()
			? $entityType->canDeleteItems(\CCrmOwnerType::Order)
			: $entityType->canDeleteItems(\CCrmOwnerType::Deal)
		;
	}

	private function getOrderIdByEntityId(int $entityTypeId, int $id): int
	{
		return match($entityTypeId)
		{
			\CCrmOwnerType::OrderPayment => Payment::getOrderIdByPaymentId($id),
			\CCrmOwnerType::OrderShipment => Shipment::getOrderIdByShipmentId($id),
			\CCrmOwnerType::OrderCheck => Check::getOrderIdByCheckId($id),
			default => 0,
		};
	}

	private function getBoundIdentifierByEntityId(int $entityTypeId, int $id): ?ItemIdentifier
	{
		return match($entityTypeId)
		{
			\CCrmOwnerType::OrderPayment => Payment::getBoundIdentifierByEntityId($id),
			\CCrmOwnerType::OrderShipment => Shipment::getBoundIdentifierByEntityId($id),
			\CCrmOwnerType::OrderCheck => Check::getBoundIdentifierByEntityId($id),
			default => null,
		};
	}
}
