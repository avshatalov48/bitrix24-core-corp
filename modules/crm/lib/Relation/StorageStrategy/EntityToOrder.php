<?php

namespace Bitrix\Crm\Relation\StorageStrategy;

use Bitrix\Crm\Binding\OrderEntityTable;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Order\Order;
use Bitrix\Crm\Relation\StorageStrategy;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectException;
use Bitrix\Main\Result;

class EntityToOrder extends StorageStrategy
{
	/**
	 * DealToOrder constructor.
	 * @throws ObjectException
	 */
	public function __construct()
	{
		if (!Loader::includeModule('sale'))
		{
			throw new ObjectException('Could not include sale module');
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getParentElements(ItemIdentifier $child, int $parentEntityTypeId): array
	{
		/** @var Order|null $order */
		$order = Order::load($child->getEntityId());
		if (!$order)
		{
			return [];
		}

		$binding = $order->getEntityBinding();
		if (
			!$binding
			|| $binding->getOwnerTypeId() !== $parentEntityTypeId
		)
		{
			return [];
		}

		return [new ItemIdentifier($binding->getOwnerTypeId(), $binding->getOwnerId())];
	}

	/**
	 * @inheritDoc
	 */
	public function getChildElements(ItemIdentifier $parent, int $childEntityTypeId): array
	{
		$children = [];

		$orderIdsList = OrderEntityTable::getOrderIdsByOwner($parent->getEntityId(), $parent->getEntityTypeId());
		foreach ($orderIdsList as $orderId)
		{
			$children[] = new ItemIdentifier(\CCrmOwnerType::Order, $orderId);
		}

		return $children;
	}

	/**
	 * @inheritDoc
	 */
	public function areItemsBound(ItemIdentifier $parent, ItemIdentifier $child): bool
	{
		/** @var Order|null $order */
		$order = Order::load($child->getEntityId());
		if (!$order)
		{
			return false;
		}

		$binding = $order->getEntityBinding();
		if (
			!$binding
			|| $binding->getOwnerTypeId() !== $parent->getEntityTypeId()
		)
		{
			return false;
		}

		return $parent->getEntityId() === $binding->getOwnerId();
	}

	/**
	 * @inheritDoc
	 */
	protected function createBinding(ItemIdentifier $parent, ItemIdentifier $child): Result
	{
		/** @var Order|null $order */
		$order = Order::load($child->getEntityId());
		if (!$order)
		{
			return (new Result())->addError(new Error('The child order does not exist: ' . $child));
		}

		$binding = $order->getEntityBinding() ?? $order->createEntityBinding();

		$binding->setField('OWNER_ID', $parent->getEntityId());
		$binding->setField('OWNER_TYPE_ID', $parent->getEntityTypeId());

		return $order->save();
	}

	/**
	 * @inheritDoc
	 */
	protected function deleteBinding(ItemIdentifier $parent, ItemIdentifier $child): Result
	{
		/** @var Order|null $order */
		$order = Order::load($child->getEntityId());
		if (!$order)
		{
			return (new Result())->addError(new Error('The child order does not exist: ' . $child));
		}

		$binding = $order->getEntityBinding();
		if (
			!$binding
			|| $binding->getOwnerTypeId() !== $parent->getEntityTypeId()
		)
		{
			return (new Result())->addError(new Error('Could not find parent item: ' . $parent));
		}

		$binding->delete();

		return $order->save();
	}

	protected function replaceBindings(ItemIdentifier $fromItem, ItemIdentifier $toItem): Result
	{
		OrderEntityTable::rebind(
			$toItem->getEntityTypeId(),
			$fromItem->getEntityId(),
			$toItem->getEntityId()
		);

		return new Result();
	}
}
