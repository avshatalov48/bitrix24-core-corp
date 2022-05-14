<?php

namespace Bitrix\Crm\Order;

use Bitrix\Crm\Binding\OrderContactCompanyTable;
use Bitrix\Main\NotSupportedException;

if (!\Bitrix\Main\Loader::includeModule('sale'))
{
	return;
}

class ContactCompanyBinding
{
	protected $entityTypeId;

	public function __construct(int $entityTypeId)
	{
		$this->entityTypeId = $entityTypeId;
	}

	/**
	 * Replace all bindings of a contact/company to another ones
	 *
	 * @param int $oldEntityId
	 * @param int $newEntityId
	 * @return void
	 */
	public function rebind(int $oldEntityId, int $newEntityId)
	{
		\Bitrix\Crm\Binding\OrderContactCompanyTable::rebind($this->entityTypeId, $oldEntityId, $newEntityId);
	}

	/**
	 * Remove all bindings of a contact/company
	 *
	 * @param int $entityId
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function unbind(int $entityId)
	{
		$itemsToUpdatePrimaryFlag = OrderContactCompanyTable::query()
			->where('ENTITY_ID', $entityId)
			->where('ENTITY_TYPE_ID', $this->entityTypeId)
			->where('IS_PRIMARY', true)
			->setSelect(['ID', 'ORDER_ID', ])
			->exec()
		;
		$orderIds = [];
		while($item = $itemsToUpdatePrimaryFlag->fetch())
		{
			$orderIds[] = $item['ORDER_ID'];
		}

		// remove from db:
		OrderContactCompanyTable::unbind($this->entityTypeId, $entityId);

		// update IS_PRIMARY:
		$this->updateIsPrimaryBulk($orderIds);
	}

	/**
	 * Bulk bind a contact/company to orders
	 *
	 * @param int $entityId
	 * @param array $orderIds
	 * @return void
	 * @throws NotSupportedException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function bindToOrders(int $entityId, array $orderIds)
	{
		foreach ($orderIds as $orderId)
		{
			$order = \Bitrix\Crm\Order\Order::load($orderId);
			if (!$order)
			{
				continue;
			}

			$contactCompanyCollection = $order->getContactCompanyCollection();
			$item = $this->createEntity($contactCompanyCollection);
			$item->setField('ENTITY_ID', $entityId);
			$item->setField('ENTITY_TYPE_ID', $this->entityTypeId);
			$contactCompanyCollection->addItem($item);
			if (
				$this->entityTypeId == \CCrmOwnerType::Contact
				&& !$contactCompanyCollection->getPrimaryContact()
			)
			{
				$item->setField('IS_PRIMARY', 'Y');
			}
			if (
				$this->entityTypeId == \CCrmOwnerType::Company
				&& !$contactCompanyCollection->getPrimaryCompany()
			)
			{
				$item->setField('IS_PRIMARY', 'Y');
			}
			$order->save();
		}
	}

	/**
	 * Bulk remove bindings of a contact/company from list of orders
	 *
	 * @param int $entityId
	 * @param array $orderIds
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function unbindFromOrders(int $entityId, array $orderIds)
	{
		$bindings = OrderContactCompanyTable::query()
			->where('ENTITY_TYPE_ID', $this->entityTypeId)
			->where('ENTITY_ID', $entityId)
			->whereIn('ORDER_ID', $orderIds)
			->setSelect(['ID', 'ORDER_ID', 'IS_PRIMARY',])
			->exec()
		;
		$needUpdatePrimaryInOrders = [];

		while ($binding = $bindings->fetch())
		{
			OrderContactCompanyTable::delete($binding['ID']);
			if ($binding['IS_PRIMARY'] === 'Y')
			{
				$needUpdatePrimaryInOrders[] = $binding['ORDER_ID'];
			}
		}

		$this->updateIsPrimaryBulk($needUpdatePrimaryInOrders);
	}

	protected function updateIsPrimaryBulk(array $orderIds): void
	{
		$orderIds = array_unique($orderIds);

		foreach ($orderIds as $orderId)
		{
			$newPrimaryItem = OrderContactCompanyTable::query()
				->where('ENTITY_TYPE_ID', $this->entityTypeId)
				->where('ORDER_ID', $orderId)
				->setSelect(['ID'])
				->fetch()
			;
			if ($newPrimaryItem)
			{
				OrderContactCompanyTable::update($newPrimaryItem['ID'], ['IS_PRIMARY' => true]);
			}
		}
	}

	protected function createEntity(ContactCompanyCollection $collection)
	{
		if ($this->entityTypeId === \CCrmOwnerType::Contact)
		{
			return Contact::create($collection);
		}
		if ($this->entityTypeId === \CCrmOwnerType::Company)
		{
			return Contact::create($collection);
		}

		throw new NotSupportedException('Entity type ' . \CCrmOwnerType::ResolveName($this->entityTypeId).' not supported');
	}
}
