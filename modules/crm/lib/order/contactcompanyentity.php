<?php

namespace Bitrix\Crm\Order;

use Bitrix\Crm\Binding\OrderContactCompanyTable;
use Bitrix\Sale;
use Bitrix\Crm;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class ContactCompanyEntity
 * @package Bitrix\Crm\Order
 */
abstract class ContactCompanyEntity extends Sale\Internals\CollectableEntity
{
	/**
	 * @return string
	 */
	public static function getRegistryType()
	{
		return Sale\Registry::REGISTRY_TYPE_ORDER;
	}

	/**
	 * @throws Main\NotImplementedException
	 * @return int
	 */
	public static function getEntityType()
	{
		throw new Main\NotImplementedException();
	}

	/**
	 * @return string
	 * @throws Main\NotImplementedException
	 */
	public static function getEntityTypeName()
	{
		throw new Main\NotImplementedException();
	}

	/**
	 * @return array
	 */
	public static function getAvailableFields()
	{
		return array(
			'ORDER_ID', 'ENTITY_ID', 'ENTITY_TYPE_ID',
			'ROLE_ID', 'IS_PRIMARY', 'SORT', 'XML_ID'
		);
	}

	/**
	 * @return array
	 */
	protected static function getMeaningfulFields()
	{
		return array();
	}

	/**
	 * @return array
	 */
	protected static function getFieldsMap()
	{
		return OrderContactCompanyTable::getMap();
	}

	/**
	 * @param ContactCompanyCollection $collection
	 * @return mixed
	 * @throws Main\ArgumentException
	 * @throws Main\NotImplementedException
	 * @throws Main\SystemException
	 */
	public static function create(ContactCompanyCollection $collection)
	{
		$fields = array(
			'ENTITY_TYPE_ID' => static::getEntityType(),
			'XML_ID' => static::generateXmlId(),
		);

		$contact = static::createEntityObject($fields);
		$contact->setCollection($collection);

		return $contact;
	}

	/**
	 * @return string
	 */
	public static function generateXmlId()
	{
		return uniqid('bx_');
	}

	/**
	 * @param array $fields
	 * @return mixed
	 * @throws Main\ArgumentException
	 * @throws Main\SystemException
	 */
	private static function createEntityObject(array $fields = array())
	{
		$registry = Sale\Registry::getInstance(static::getRegistryType());
		$entityClassName = $registry->get(static::getRegistryEntity());

		return new $entityClassName($fields);
	}

	/**
	 * @param $name
	 * @param $value
	 * @return Sale\Result
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\NotImplementedException
	 * @throws \Exception
	 */
	public function setField($name, $value)
	{
		if ($name === 'IS_PRIMARY'
			&& $value === 'Y'
		)
		{
			/** @var ContactCompanyCollection $collection */
			$collection = $this->getCollection();
			if ($collection)
			{
				if ($collection->isPrimaryItemExists(static::getEntityType()))
				{
					throw new Main\SystemException('Primary entity has already existed');
				}
			}
		}

		return parent::setField($name, $value);
	}

	/**
	 * @param $id
	 * @return array|false
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function loadForOrder($id)
	{
		if (intval($id) <= 0)
		{
			throw new Main\ArgumentNullException("id");
		}

		$entityList = array();

		$registry = Sale\Registry::getInstance(static::getRegistryType());

		/** @var ContactCompanyCollection $contactCompanyCollection */
		$contactCompanyCollection = $registry->get(ENTITY_CRM_CONTACT_COMPANY_COLLECTION);
		$dbRes = $contactCompanyCollection::getList(
			array(
				'filter' => array(
					'ORDER_ID' => $id,
					'ENTITY_TYPE_ID' => static::getEntityType()
				)
			)
		);
		while ($data = $dbRes->fetch())
		{
			$entityList[] = static::createEntityObject($data);
		}

		return $entityList;
	}

	/**
	 * @return Sale\Result
	 * @throws \Exception
	 */
	public function save()
	{
		$result = new Sale\Result();

		if (!$this->isChanged())
		{
			return $result;
		}

		$id = $this->getId();

		if ($id > 0)
		{
			$fields = $this->getFields()->getChangedValues();
			$r = $this->updateInternal($id, $fields);
		}
		else
		{
			$fields = $this->getFields()->getValues();

			/** @var ContactCompanyCollection $collection */
			$collection = $this->getCollection();

			/** @var Order $order */
			$order = $collection->getOrder();

			$fields['ORDER_ID'] = $order->getId();
			$this->setFieldNoDemand('ORDER_ID', $fields['ORDER_ID']);

			$r = $this->addInternal($fields);
			if ($r->isSuccess())
			{
				$id = $r->getId();
				$this->setFieldNoDemand('ID', $id);
			}
		}

		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
		}

		$result->setId($id);

		return $result;
	}

	/**
	 * @return bool
	 */
	public function isPrimary()
	{
		return $this->getField('IS_PRIMARY') === 'Y';
	}

	/**
	 * @param $primary
	 * @param array $data
	 * @return Main\Entity\UpdateResult
	 * @throws \Exception
	 */
	protected function updateInternal($primary, array $data)
	{
		return OrderContactCompanyTable::update($primary, $data);
	}

	/**
	 * @param array $data
	 * @return Main\Entity\AddResult
	 * @throws \Exception
	 */
	protected function addInternal(array $data)
	{
		return OrderContactCompanyTable::add($data);
	}

	/**
	 * @return array
	 * @throws Main\NotImplementedException
	 */
	public function getBankRequisiteList()
	{
		$entityBankDetail = new Crm\EntityBankDetail();

		$dbRes = $entityBankDetail->getList([
			'filter' => [
				'=ENTITY_ID' => $this->getField('ENTITY_ID'),
				'=ENTITY_TYPE_ID' => static::getEntityType()
			],
			'order' => ["SORT" => "ASC", "ID"=>"ASC"],
		]);

		$result = [];
		while ($data = $dbRes->fetch())
		{
			$result[$data['ID']] = $data;
		}

		return $result;
	}

	/**
	 * @return array
	 * @throws Main\NotImplementedException
	 */
	public function getRequisiteList()
	{
		$entityRequisite = new Crm\EntityRequisite();

		$dbRes = $entityRequisite->getList([
			'filter' => [
				'=ENTITY_ID' => $this->getField('ENTITY_ID'),
				'=ENTITY_TYPE_ID' => static::getEntityType()
			],
			'order' => ["SORT" => "ASC", "ID"=>"ASC"],
		]);

		$result = [];
		while ($data = $dbRes->fetch())
		{
			$result[$data['ID']] = $data;
		}

		return $result;
	}

	/**
	 * @return string|null
	 */
	public function getCustomerName(): ?string
	{
		return null;
	}

	public static function rebind(int $oldEntityId, int $newEntityId)
	{
		\Bitrix\Crm\Binding\OrderContactCompanyTable::rebind(static::getEntityType(), $oldEntityId, $newEntityId);
	}

	public static function unbind(int $entityId)
	{
		$itemsToUpdatePrimaryFlag = OrderContactCompanyTable::query()
			->where('ENTITY_ID', $entityId)
			->where('ENTITY_TYPE_ID', static::getEntityType())
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
		OrderContactCompanyTable::unbind(static::getEntityType(), $entityId);

		// update IS_PRIMARY:
		static::updateIsPrimaryBulk($orderIds);
	}

	public static function unbindFromOrders(int $entityId, array $orderIds)
	{
		$bindings = OrderContactCompanyTable::query()
			->where('ENTITY_TYPE_ID',static::getEntityType())
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

		static::updateIsPrimaryBulk($needUpdatePrimaryInOrders);
	}

	public static function bindToOrders(int $entityId, array $orderIds)
	{
		foreach ($orderIds as $orderId)
		{
			$order = \Bitrix\Crm\Order\Order::load($orderId);
			if (!$order)
			{
				continue;
			}
			$entityTypeId = static::getEntityType();
			$contactCompanyCollection = $order->getContactCompanyCollection();
			$item = static::create($contactCompanyCollection);
			$item->setField('ENTITY_ID', $entityId);
			$item->setField('ENTITY_TYPE_ID', $entityTypeId);
			$contactCompanyCollection->addItem($item);
			if (
				$entityTypeId ==  \CCrmOwnerType::Contact
				&& !$contactCompanyCollection->getPrimaryContact()
			)
			{
				$item->setField('IS_PRIMARY', 'Y');
			}
			if (
				$entityTypeId == \CCrmOwnerType::Company
				&& !$contactCompanyCollection->getPrimaryCompany()
			)
			{
				$item->setField('IS_PRIMARY', 'Y');
			}
			$item->save();
		}
	}

	protected static function updateIsPrimaryBulk(array $orderIds): void
	{
		$orderIds = array_unique($orderIds);

		foreach ($orderIds as $orderId)
		{
			$newPrimaryItem = OrderContactCompanyTable::query()
				->where('ENTITY_TYPE_ID', static::getEntityType())
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
}
