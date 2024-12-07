<?php

namespace Bitrix\Crm\Order;

use Bitrix\Crm\Binding;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main;
use Bitrix\Sale;
use Bitrix\Sale\Internals\CollectableEntity;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class ContactCompanyCollection
 * @package Bitrix\Crm\Order
 */
class ContactCompanyCollection extends Sale\Internals\EntityCollection
{
	protected $order = null;

	/** @var bool $autoCreationMode */
	private $autoCreationMode = true;

	/**
	 * @return Sale\Internals\Entity
	 */
	protected function getEntityParent()
	{
		return $this->order;
	}

	/**
	 * @return null
	 */
	public function getOrder()
	{
		return $this->order;
	}

	/**
	 * @return ContactCompanyCollection
	 * @throws \Bitrix\Main\ArgumentException
	 */
	private static function createCollectionObject()
	{
		$registry = Sale\Registry::getInstance(static::getRegistryType());
		$className = $registry->get(ENTITY_CRM_CONTACT_COMPANY_COLLECTION);

		return new $className();
	}

	/**
	 * @return string
	 */
	public static function getRegistryType()
	{
		return Sale\Registry::REGISTRY_TYPE_ORDER;
	}

	/**
	 * @param Order $order
	 */
	public function setOrder(Order $order)
	{
		$this->order = $order;
	}

	/**
	 * @param Order $order
	 * @return ContactCompanyCollection
	 */
	public static function load(Order $order)
	{
		$collection = static::createCollectionObject();
		$collection->setOrder($order);

		if (!$order->isNew())
		{
			$contactClassName = static::getContactClassName();
			$contactList = $contactClassName::loadForOrder($order->getId());
			/** @var Contact $contact */
			foreach ($contactList as $contact)
			{
				$contact->setCollection($collection);
				$collection->bindItem($contact);
			}

			$companyClassName = static::getCompanyClassName();
			/** @var Company $company */
			$companyList = $companyClassName::loadForOrder($order->getId());
			foreach ($companyList as $company)
			{
				$company->setCollection($collection);
				$collection->bindItem($company);
			}
		}

		return $collection;
	}

	/**
	 * @return mixed
	 * @throws \Bitrix\Main\ArgumentException
	 */
	protected static function getCompanyClassName()
	{
		$registry = Sale\Registry::getInstance(static::getRegistryType());

		return $registry->get(ENTITY_CRM_COMPANY);
	}

	/**
	 * @return mixed
	 * @throws \Bitrix\Main\ArgumentException
	 */
	protected static function getContactClassName()
	{
		$registry = Sale\Registry::getInstance(static::getRegistryType());

		return $registry->get(ENTITY_CRM_CONTACT);
	}

	/**
	 * @param array $parameters
	 * @return \Bitrix\Main\DB\Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getList(array $parameters = array())
	{
		return Binding\OrderContactCompanyTable::getList($parameters);
	}

	/**
	 * @param CollectableEntity $item
	 * @return CollectableEntity
	 */
	public function addItem(CollectableEntity $item)
	{
		return parent::addItem($item);
	}

	/**
	 * @param CollectableEntity $item
	 * @return CollectableEntity
	 * @throws Main\NotImplementedException
	 * @throws Main\NotSupportedException
	 * @throws Main\SystemException
	 */
	protected function bindItem(CollectableEntity $item): CollectableEntity
	{
		if (!($item instanceof ContactCompanyEntity))
		{
			throw new Main\NotSupportedException();
		}

		if ($item instanceof Company
			&& $this->getCompanies()->count() === 1)
		{
			throw new Main\SystemException('Multiple companies are not supported');
		}

		if ($this->isItemExists($item))
		{
			throw new Main\SystemException('Addable item has already existed');
		}

		if ($item->isPrimary()
			&& $this->isPrimaryItemExists($item::getEntityType())
		)
		{
			throw new Main\SystemException('Primary '.mb_strtolower($item::getEntityTypeName()).' has already existed');
		}

		return parent::bindItem($item);
	}

	/**
	 * @param CollectableEntity $item
	 * @return bool
	 */
	private function isItemExists(CollectableEntity $item)
	{
		/** @var ContactCompanyEntity $entity */
		foreach ($this->collection as $entity)
		{
			if ((int)$entity->getField('ENTITY_ID') === (int)$item->getField('ENTITY_ID')
				&& (int)$entity->getField('ENTITY_TYPE_ID') === (int)$item->getField('ENTITY_TYPE_ID')
			)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @param $type
	 * @return bool
	 */
	public function isPrimaryItemExists($type)
	{
		/** @var ContactCompanyEntity $entity */
		foreach ($this->collection as $entity)
		{
			if ($entity->getField('IS_PRIMARY') === 'Y'
				&& (int)$entity->getField('ENTITY_TYPE_ID') === $type
			)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @return Sale\Internals\CollectionFilterIterator
	 */
	public function getContacts()
	{
		$callback = function (ContactCompanyEntity $entity)
		{
			return $entity instanceof Contact;
		};

		return new Sale\Internals\CollectionFilterIterator($this->getIterator(), $callback);
	}

	/**
	 * @return Sale\Internals\CollectionFilterIterator
	 */
	public function getCompanies()
	{
		$callback = function (ContactCompanyEntity $entity)
		{
			return $entity instanceof Company;
		};

		return new Sale\Internals\CollectionFilterIterator($this->getIterator(), $callback);
	}

	/**
	 * Returns the company that is bound to the order
	 * Order doesn't supports multiple companies
	 * For details, @see ContactCompanyCollection::addItem()
	 *
	 * @return Company|null
	 */
	public function getCompany(): ?Company
	{
		/** @var Company[] $companies */
		$companies = iterator_to_array($this->getCompanies());

		return array_shift($companies);
	}

	/**
	 * Returns an entity specified by the identifier
	 *
	 * @param ItemIdentifier $identifier
	 *
	 * @return ContactCompanyEntity|null
	 */
	public function getItemByIdentifier(ItemIdentifier $identifier): ?ContactCompanyEntity
	{
		/** @var ContactCompanyEntity $item */
		foreach ($this->collection as $item)
		{
			if (
				((int)$item->getField('ENTITY_TYPE_ID') === $identifier->getEntityTypeId())
				&& ((int)$item->getField('ENTITY_ID') === $identifier->getEntityId())
			)
			{
				return $item;
			}
		}

		return null;
	}

	/**
	 * @return Sale\Result
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectNotFoundException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * @throws \Exception
	 */
	public function save()
	{
		$result = new Sale\Result();

		/** @var Order $order */
		if (!$order = $this->getEntityParent())
		{
			throw new Main\ObjectNotFoundException('Entity "Order" not found');
		}

		if (!$order->isNew())
		{
			$itemsFromDbList = static::getList(
				array(
					"filter" => array("ORDER_ID" => $order->getId()),
				)
			);
			while ($item = $itemsFromDbList->fetch())
			{
				if (!$this->getItemById($item['ID']))
				{
					static::deleteInternal($item['ID']);
				}
			}
		}

		/** @var ContactCompanyEntity $entity */
		foreach ($this->collection as $entity)
		{
			$r = $entity->save();
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}
		}

		$this->clearChanged();

		return $result;
	}

	/**
	 * @return Company|null
	 */
	public function getPrimaryCompany()
	{
		$companies = $this->getCompanies();
		foreach ($companies as $company)
		{
			if ($company->isPrimary())
			{
				return $company;
			}
		}

		return null;
	}

	/**
	 * @return Contact|null
	 */
	public function getPrimaryContact()
	{
		$contacts = $this->getContacts();
		/** @var Contact $contact */
		foreach ($contacts as $contact)
		{
			if ($contact->isPrimary())
			{
				return $contact;
			}
		}

		return null;
	}

	/**
	 * @internal
	 *
	 * @param $idOrder
	 * @return Sale\Result
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function deleteNoDemand($idOrder)
	{
		$result = new Sale\Result();

		$dbRes = static::getList(
			array(
				"filter" => array("=ORDER_ID" => $idOrder),
				"select" => array("ID"),
			)
		);

		while ($entity = $dbRes->fetch())
		{
			$r = static::deleteInternal($entity['ID']);
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}
		}

		return $result;
	}

	/**
	 * @param $primary
	 * @return Main\Entity\DeleteResult
	 * @throws \Exception
	 */
	protected static function deleteInternal($primary)
	{
		return Binding\OrderContactCompanyTable::delete($primary);
	}

	/**
	 * @return mixed
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentTypeException
	 * @throws Main\SystemException
	 */
	public function createContact()
	{
		$contactClassName = static::getContactClassName();
		$contact = $contactClassName::create($this);
		$this->addItem($contact);

		return $contact;
	}

	/**
	 * @internal
	 * @param \SplObjectStorage $cloneEntity
	 *
	 * @return ContactCompanyCollection
	 */
	public function createClone(\SplObjectStorage $cloneEntity)
	{
		if ($this->isClone() && $cloneEntity->contains($this))
		{
			return $cloneEntity[$this];
		}

		/** @var ContactCompanyCollection $contactCompanyCollection */
		$contactCompanyCollection = parent::createClone($cloneEntity);

		if ($this->order)
		{
			if ($cloneEntity->contains($this->order))
			{
				$contactCompanyCollection->order = $cloneEntity[$this->order];
			}
		}

		return $contactCompanyCollection;
	}

	/**
	 * @return mixed
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentTypeException
	 * @throws Main\SystemException
	 */
	public function createCompany()
	{
		$companyClassName = static::getCompanyClassName();
		$company = $companyClassName::create($this);
		$this->addItem($company);

		return $company;
	}

	/**
	 * @return Company|Contact|null
	 */
	public function getEntityCommunication()
	{
		$contact = $this->getPrimaryContact();
		if ($contact)
		{
			return $contact;
		}

		$company = $this->getPrimaryCompany();
		if ($company)
		{
			return $company;
		}

		return null;
	}

	/**
	 * @return string|null
	 */
	public function getEntityCommunicationPhone(): ?string
	{
		$entityCommunication = $this->getEntityCommunication();
		if (!$entityCommunication)
		{
			return null;
		}

		$phone = \CCrmFieldMulti::GetEntityFirstPhone(
			$entityCommunication::getEntityTypeName(),
			$entityCommunication->getField('ENTITY_ID'),
			true,
			false
		);

		if (!$phone)
		{
			return null;
		}

		return (string)$phone->format();
	}

	public function disableAutoCreationMode(): void
	{
		$this->autoCreationMode = false;
	}

	public function isAutoCreationModeEnabled(): bool
	{
		return $this->autoCreationMode;
	}
}
