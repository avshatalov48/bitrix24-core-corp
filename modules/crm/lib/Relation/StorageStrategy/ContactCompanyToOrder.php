<?php

namespace Bitrix\Crm\Relation\StorageStrategy;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Order\ContactCompanyCollection;
use Bitrix\Crm\Order\ContactCompanyEntity;
use Bitrix\Crm\Order\Order;
use Bitrix\Crm\Relation\StorageStrategy;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectException;
use Bitrix\Main\Result;

abstract class ContactCompanyToOrder extends StorageStrategy
{
	/**
	 * ContactCompanyToOrder constructor.
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

		$parents = [];
		/** @var ContactCompanyEntity $client */
		foreach ($order->getContactCompanyCollection() as $client)
		{
			if ($client::getEntityType() === $parentEntityTypeId)
			{
				$parents[] = new ItemIdentifier($client::getEntityType(), $client->getField('ENTITY_ID'));
			}
		}

		return $parents;
	}

	/**
	 * @inheritDoc
	 */
	public function getChildElements(ItemIdentifier $parent, int $childEntityTypeId): array
	{
		$result = ContactCompanyCollection::getList([
			'select' => ['ORDER_ID'],
			'filter' => [
				'=ENTITY_TYPE_ID' => $parent->getEntityTypeId(),
				'=ENTITY_ID' => $parent->getEntityId(),
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

		/** @var ContactCompanyEntity|null $client */
		$client = $order->getContactCompanyCollection()->getItemByIdentifier($parent);

		return !is_null($client);
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

		$entity = $this->getEntity($order);

		$entity->setField('ENTITY_ID', $parent->getEntityId());

		return $order->save();
	}

	abstract protected function getEntity(Order $order): ContactCompanyEntity;

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

		/** @var ContactCompanyEntity|null $client */
		$client = $order->getContactCompanyCollection()->getItemByIdentifier($parent);
		if (!$client)
		{
			return (new Result())->addError(new Error('Could not find the parent entity: ' . $parent));
		}

		$client->delete();

		$this->afterBindingDeletion($order);

		return $order->save();
	}

	protected function afterBindingDeletion(Order $order): void
	{
	}
}
