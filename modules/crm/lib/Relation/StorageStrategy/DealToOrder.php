<?php

namespace Bitrix\Crm\Relation\StorageStrategy;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Order\DealBinding;
use Bitrix\Crm\Order\Order;
use Bitrix\Crm\Relation\StorageStrategy;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectException;
use Bitrix\Main\Result;

class DealToOrder extends StorageStrategy
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

		$dealBinding = $order->getDealBinding();
		if (!$dealBinding)
		{
			return [];
		}

		return [new ItemIdentifier(\CCrmOwnerType::Deal, $dealBinding->getDealId())];
	}

	/**
	 * @inheritDoc
	 */
	public function getChildElements(ItemIdentifier $parent, int $childEntityTypeId): array
	{
		$result = DealBinding::getList([
			'select' => ['ORDER_ID'],
			'filter' => [
				'=DEAL_ID' => $parent->getEntityId(),
			],
		]);

		$children = [];
		while ($row = $result->fetch())
		{
			/** @var array $row */
			$children[] = new ItemIdentifier(\CCrmOwnerType::Order, (int)$row['ORDER_ID']);
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

		$dealBinding = $order->getDealBinding();
		if (!$dealBinding)
		{
			return false;
		}

		return ($parent->getEntityId() === $dealBinding->getDealId());
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

		$dealBinding = $order->getDealBinding() ?? $order->createDealBinding();

		$dealBinding->setField('DEAL_ID', $parent->getEntityId());

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

		$dealBinding = $order->getDealBinding();
		if (!$dealBinding)
		{
			return (new Result())->addError(new Error('Could not find the parent deal: ' . $parent));
		}

		$dealBinding->delete();

		return $order->save();
	}
}
