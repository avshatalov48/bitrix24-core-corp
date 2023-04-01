<?php

namespace Bitrix\Crm\Relation;

use Bitrix\Crm\Binding\EntityBinding;
use Bitrix\Crm\Comparer\ComparerBase;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Service\EventHistory;
use Bitrix\Crm\Service\EventHistory\TrackedObject;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\Error;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\Result;

/**
 * Use this service to register relation changes in user interface.
 * Since not all the changes should be registered, this functionality extracted to a separate service.
 * If you want user to know that some relations had changed, use this service explicitly.
 */
final class Registrar
{
	private Container $container;
	private EventHistory $history;

	public function __construct()
	{
		$this->container = Container::getInstance();
		$this->history = $this->container->getEventHistory();
	}

	public function registerByFieldsChange(
		ItemIdentifier $child,
		array $fieldsInfo,
		array $previousFields,
		array $currentFields,
		array $itemsToIgnore = [],
		Context $context = null
	): Result
	{
		$result = new Result();

		$difference = ComparerBase::compareEntityFields($previousFields, $currentFields);

		foreach ($fieldsInfo as $fieldName => $singleFieldInfo)
		{
			$parentEntityTypeId = (int)($singleFieldInfo['SETTINGS']['parentEntityTypeId'] ?? null);
			if (!\CCrmOwnerType::IsDefined($parentEntityTypeId) || $parentEntityTypeId === \CCrmOwnerType::Order)
			{
				continue;
			}

			if (!$difference->isChanged($fieldName))
			{
				continue;
			}

			$previousValue = (int)$difference->getPreviousValue($fieldName);
			if ($previousValue > 0)
			{
				$oldParent = new ItemIdentifier($parentEntityTypeId, $previousValue);

				// intentional not strict comparison. it doesn't matter here if objects are the same instance
				if (!in_array($oldParent, $itemsToIgnore, false))
				{
					$unbindResult = $this->registerUnbind($oldParent, $child, $context);
					if (!$unbindResult->isSuccess())
					{
						$result->addErrors($unbindResult->getErrors());
					}
				}
			}

			$currentValue = (int)$difference->getCurrentValue($fieldName);
			if ($currentValue > 0)
			{
				$newParent = new ItemIdentifier($parentEntityTypeId, $currentValue);

				// intentional not strict comparison. it doesn't matter here if objects are the same instance
				if (!in_array($newParent, $itemsToIgnore, false))
				{
					$bindResult = $this->registerBind($newParent, $child, $context);
					if (!$bindResult->isSuccess())
					{
						$result->addErrors($bindResult->getErrors());
					}
				}
			}
		}

		return $result;
	}

	/**
	 * @param ItemIdentifier $child
	 * @param int $parentEntityTypeId
	 * @param array[] $previousBindings
	 * @param array[] $currentBindings
	 * @param ItemIdentifier[] $itemsToIgnore
	 * @param Context|null $context
	 * @return Result
	 */
	public function registerByBindingsChange(
		ItemIdentifier $child,
		int $parentEntityTypeId,
		array $previousBindings,
		array $currentBindings,
		array $itemsToIgnore = [],
		Context $context = null
	): Result
	{
		$result = new Result();

		[$bound, $unbound] = EntityBinding::prepareBoundAndUnboundEntities(
			$parentEntityTypeId,
			$previousBindings,
			$currentBindings
		);

		foreach (EntityBinding::prepareEntityIDs($parentEntityTypeId, $unbound) as $removedParentId)
		{
			$oldParent = new ItemIdentifier($parentEntityTypeId, $removedParentId);

			// intentional not strict comparison. it doesn't matter here if objects are the same instance
			if (!in_array($oldParent, $itemsToIgnore, false))
			{
				$unbindResult = $this->registerUnbind($oldParent, $child, $context);
				if (!$unbindResult->isSuccess())
				{
					$result->addErrors($unbindResult->getErrors());
				}
			}
		}

		foreach (EntityBinding::prepareEntityIDs($parentEntityTypeId, $bound) as $addedParentId)
		{
			$newParent = new ItemIdentifier($parentEntityTypeId, $addedParentId);

			// intentional not strict comparison. it doesn't matter here if objects are the same instance
			if (!in_array($newParent, $itemsToIgnore, false))
			{
				$bindResult = $this->registerBind($newParent, $child, $context);
				if (!$bindResult->isSuccess())
				{
					$result->addErrors($bindResult->getErrors());
				}
			}
		}

		return $result;
	}

	public function registerBind(
		ItemIdentifier $parent,
		ItemIdentifier $child,
		Context $context = null
	): Result
	{
		[$parentObject, $childObject, $result] = $this->getTrackedObjects($parent, $child);
		if (!$result->isSuccess())
		{
			$result->addError(new Error('Could not get ' . TrackedObject::class . ' for bind registration'));

			return $result;
		}

		return $this->history->registerBind($parentObject, $childObject, $context);
	}

	public function registerUnbind(
		ItemIdentifier $parent,
		ItemIdentifier $child,
		Context $context = null
	): Result
	{
		[$parentObject, $childObject, $result] = $this->getTrackedObjects($parent, $child);
		if (!$result->isSuccess())
		{
			$result->addError(new Error('Could not get ' . TrackedObject::class . ' for unbind registration'));

			return $result;
		}

		return $this->history->registerUnbind($parentObject, $childObject, $context);
	}

	private function getTrackedObjects(ItemIdentifier $parent, ItemIdentifier $child): array
	{
		$result = new Result();

		$parentFactory = $this->container->getFactory($parent->getEntityTypeId());
		if (!$parentFactory)
		{
			return [
				null,
				null,
				$result->addError(new Error('Parent factory not found')),
			];
		}

		$childFactory = $this->container->getFactory($child->getEntityTypeId());
		if (!$childFactory)
		{
			return [
				null,
				null,
				$result->addError(new Error('Child factory not found')),
			];
		}

		$parentItem = $this->getItem($parentFactory, $parent);
		if (!$parentItem)
		{
			return [
				null,
				null,
				$result->addError(new Error('Parent item not found')),
			];
		}

		$childItem = $this->getItem($childFactory, $child);
		if (!$childItem)
		{
			return [
				null,
				null,
				$result->addError(new Error('Child item not found')),
			];
		}

		return [
			$parentFactory->getTrackedObject($parentItem),
			$childFactory->getTrackedObject($childItem),
			$result,
		];
	}

	private function getItem(Factory $factory, ItemIdentifier $identifier): ?Item
	{
		$broker = $this->container->getEntityBroker($identifier->getEntityTypeId());
		if ($broker)
		{
			//we don't really care if the item from broker is consistent with the state of an actual item in db
			//we don't use its fields values anywhere. if it changes, implement cache actualization please
			$item = $broker->getById($identifier->getEntityId());
			if ($item instanceof Item)
			{
				return $item;
			}

			if ($item instanceof EntityObject)
			{
				return $factory->getItemByEntityObject($item);
			}
		}

		return $factory->getItem($identifier->getEntityId());
	}
}
