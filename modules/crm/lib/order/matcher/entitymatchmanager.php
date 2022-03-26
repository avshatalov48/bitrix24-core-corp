<?php

namespace Bitrix\Crm\Order\Matcher;

use Bitrix\Crm\Order\Matcher\Internals\FormTable;
use Bitrix\Crm\Order\Matcher\Internals\OrderPropsMatchTable;
use Bitrix\Sale\Order;
use Bitrix\Sale\PropertyValue;

class EntityMatchManager
{
	/** @var EntityMatchManager $instance */
	private static $instance = null;

	/** @var array $propertyMap */
	protected $propertyMap = [];

	private function __construct()
	{
	}

	private function __clone()
	{
	}

	public static function getInstance()
	{
		if (!isset(static::$instance))
		{
			static::$instance = new static;
		}

		return static::$instance;
	}

	protected function getOrderPropertyValues(Order $order)
	{
		$properties = [];

		/** @var PropertyValue $property */
		foreach ($order->getPropertyCollection() as $property)
		{
			$properties[$property->getPropertyId()] = $property->getValue();
		}

		return $properties;
	}

	protected function loadPropertyMap(array $propertyIds)
	{
		$propertyIdsToLoad = array_diff($propertyIds, array_fill_keys($this->propertyMap, true));

		if (!empty($propertyIdsToLoad))
		{
			$propertyBindingIterator = OrderPropsMatchTable::getList([
				'filter' => [
					'SALE_PROP_ID' => $propertyIdsToLoad
				]
			]);
			foreach ($propertyBindingIterator->fetchAll() as $binding)
			{
				$this->propertyMap[$binding['SALE_PROP_ID']][] = $binding;
			}
		}
	}

	protected function getPropertyMap(array $propertyIds)
	{
		$this->loadPropertyMap($propertyIds);

		return array_intersect_key($this->propertyMap, array_fill_keys($propertyIds, true));
	}

	protected function checkEntityType($property)
	{
		if (isset($property['CRM_ENTITY_TYPE']) && \CCrmOwnerType::IsEntity($property['CRM_ENTITY_TYPE']))
		{
			$entityType = (int)$property['CRM_ENTITY_TYPE'];
		}
		else
		{
			$entityType = \CCrmOwnerType::Undefined;
		}

		return $entityType;
	}

	protected function getEntityProperties(Order $order)
	{
		$entityProperties = [];

		$propertyValues = $this->getOrderPropertyValues($order);
		$propertyMap = $this->getPropertyMap(array_keys($propertyValues));

		foreach ($propertyMap as $propertyId => $properties)
		{
			foreach ($properties as $property)
			{
				$entityType = $this->checkEntityType($property);

				$property['VALUE'] = $propertyValues[$propertyId];

				$entityProperties[$entityType][$property['CRM_FIELD_TYPE']][$property['ID']] = $property;
			}
		}

		return $entityProperties;
	}

	protected function getAssignedById(Order $order)
	{
		$personTypeId = $order->getPersonTypeId();

		$responsibleQueue = new ResponsibleQueue($personTypeId);
		$responsibleId = $responsibleQueue->getNextId();

		return $responsibleId ?: 1;
	}

	protected function getDuplicateMode(Order $order)
	{
		$personTypeId = $order->getPersonTypeId();

		return FormTable::getDuplicateModeByPersonType($personTypeId);
	}

	public function search(Order $order)
	{
		$entities = [];

		$entityProperties = $this->getEntityProperties($order);
		$duplicateMode = $this->getDuplicateMode($order);

		if (!empty($entityProperties[\CCrmOwnerType::Company]))
		{
			$matcher = new CompanyMatcher();

			$matcher->setProperties($entityProperties[\CCrmOwnerType::Company]);
			$matcher->setDuplicateControlMode($duplicateMode);

			$entities[\CCrmOwnerType::Company] = $matcher->search();
		}

		if (!empty($entityProperties[\CCrmOwnerType::Contact]))
		{
			$matcher = new ContactMatcher();

			$matcher->setProperties($entityProperties[\CCrmOwnerType::Contact]);
			$matcher->setDuplicateControlMode($duplicateMode);

			$entities[\CCrmOwnerType::Contact] = $matcher->search();
		}

		return $entities;
	}

	public function create(Order $order)
	{
		$entities = [];

		$entityProperties = $this->getEntityProperties($order);
		$assignedById = $this->getAssignedById($order);
		$duplicateMode = $this->getDuplicateMode($order);

		if (!empty($entityProperties[\CCrmOwnerType::Company]))
		{
			$matcher = new CompanyMatcher();

			$matcher->setProperties($entityProperties[\CCrmOwnerType::Company]);
			$matcher->setAssignedById($assignedById);
			$matcher->setDuplicateControlMode($duplicateMode);

			$entities[\CCrmOwnerType::Company] = $matcher->create();
		}

		if (!empty($entityProperties[\CCrmOwnerType::Contact]))
		{
			$matcher = new ContactMatcher();

			$matcher->setProperties($entityProperties[\CCrmOwnerType::Contact]);
			$matcher->setAssignedById($assignedById);
			$matcher->setDuplicateControlMode($duplicateMode);

			if (!empty($entities[\CCrmOwnerType::Company]))
			{
				$matcher->setRelation(\CCrmOwnerType::Company, $entities[\CCrmOwnerType::Company]);
			}

			$entities[\CCrmOwnerType::Contact] = $matcher->create();
		}

		return $entities;
	}

	public function matchEntityRequisites(Order $order, $entityTypeId, $entityId)
	{
		$entityProperties = $this->getEntityProperties($order);
		$assignedById = $this->getAssignedById($order);
		$duplicateMode = $this->getDuplicateMode($order);
		$matcher = null;

		if ((int)$entityTypeId === \CCrmOwnerType::Company)
		{
			$matcher = new CompanyMatcher();
		}
		elseif ((int)$entityTypeId === \CCrmOwnerType::Contact)
		{
			$matcher = new ContactMatcher();
		}

		if (!$matcher || empty($entityProperties[(int)$entityTypeId]))
		{
			return [];
		}

		$matcher->setProperties($entityProperties[(int)$entityTypeId]);
		$matcher->setAssignedById($assignedById);
		$matcher->setDuplicateControlMode($duplicateMode);

		$requisites = $matcher->matchRequisites($entityId);

		return $requisites;
	}

	public function match(Order $order)
	{
		$entities = [];

		$entityProperties = $this->getEntityProperties($order);
		$assignedById = $this->getAssignedById($order);
		$duplicateMode = $this->getDuplicateMode($order);

		if (!empty($entityProperties[\CCrmOwnerType::Company]))
		{
			$matcher = new CompanyMatcher();

			$matcher->setProperties($entityProperties[\CCrmOwnerType::Company]);
			$matcher->setAssignedById($assignedById);
			$matcher->setDuplicateControlMode($duplicateMode);

			$entities[\CCrmOwnerType::Company] = $matcher->match();
		}

		if (!empty($entityProperties[\CCrmOwnerType::Contact]))
		{
			$matcher = new ContactMatcher();

			$matcher->setProperties($entityProperties[\CCrmOwnerType::Contact]);
			$matcher->setAssignedById($assignedById);
			$matcher->setDuplicateControlMode($duplicateMode);

			if (!empty($entities[\CCrmOwnerType::Company]))
			{
				$matcher->setRelation(\CCrmOwnerType::Company, $entities[\CCrmOwnerType::Company]);
			}

			$entities[\CCrmOwnerType::Contact] = $matcher->match();
		}

		return $entities;
	}
}