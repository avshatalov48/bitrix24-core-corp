<?php

namespace Bitrix\Crm\Item\FieldImplementation;

use Bitrix\Crm\Binding\EntityBinding;
use Bitrix\Crm\Item;
use Bitrix\Crm\Item\FieldImplementation;
use Bitrix\Crm\Service\Broker;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\ORM\Entity;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\ORM\Objectify\Values;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Result;

/**
 * @internal
 */
final class Binding implements FieldImplementation
{
	/** @var EntityObject */
	private $entityObject;

	/** @var int */
	private $boundEntityTypeId;
	/** @var string */
	private $boundEntityIdFieldName;
	/** @var Binding\FieldNameMap */
	private $fieldNameMap;
	/** @var Entity */
	private $bindingEntity;

	private ?Entity $boundEntity = null;
	private string $thisIdField = '';
	private array $boundIdsBeforeSave;

	/** @var Array<string, mixed> */
	private $defaultValuesForBindingObject = [];

	/** @var Broker */
	private $broker;

	public function __construct(
		EntityObject $entityObject,
		int $boundEntityTypeId,
		Binding\FieldNameMap $fieldNameMap,
		Entity $bindingEntity,
		Broker $boundEntitiesBroker
	)
	{
		$this->entityObject = $entityObject;

		$this->boundEntityTypeId = $boundEntityTypeId;
		$this->boundEntityIdFieldName = EntityBinding::resolveEntityFieldName($boundEntityTypeId);
		if (!$fieldNameMap->isBindingsFilled())
		{
			throw new ArgumentException('bindings field is fieldNameMap should be filled', 'fieldNameMap');
		}
		$this->fieldNameMap = $fieldNameMap;
		$this->bindingEntity = $bindingEntity;

		$this->broker = $boundEntitiesBroker;
	}

	/**
	 * @param Array<string, mixed> $defaultValuesForFields
	 * @return $this
	 */
	public function setBindingObjectDefaultValues(array $defaultValuesForFields): self
	{
		$this->defaultValuesForBindingObject = $defaultValuesForFields;

		return $this;
	}

	public function configureUpdatingRefIdInBoundEntity(Entity $boundEntity, string $thisIdField): self
	{
		if (!$this->bindingEntity->hasField($thisIdField))
		{
			throw new ArgumentException("{$thisIdField} doesn't exist in Binding Entity");
		}

		$isFound = false;
		foreach ($this->bindingEntity->getFields() as $field)
		{
			if ($field instanceof Reference && $field->getRefEntity()->getFullName() === $boundEntity->getFullName())
			{
				$isFound = true;
				break;
			}
		}

		if (!$isFound)
		{
			throw new ArgumentException('The provided bound entity is not referenced by binding entity');
		}

		$this->boundEntity = $boundEntity;
		$this->thisIdField = $thisIdField;

		return $this;
	}

	//region Interface implementation
	public function getHandledFieldNames(): array
	{
		return $this->fieldNameMap->getAllFilled();
	}

	public function get(string $commonFieldName)
	{
		return $this->getValue($commonFieldName, Values::ALL);
	}

	public function set(string $commonFieldName, $value): void
	{
		if ($this->fieldNameMap->isSingleIdFilled() && $commonFieldName === $this->fieldNameMap->getSingleId())
		{
			$value = (int)$value;
			if ($value < 0)
			{
				$value = 0;
			}

			$currentIds = EntityBinding::prepareEntityIDs(
				$this->boundEntityTypeId,
				$this->get($this->fieldNameMap->getBindings()),
			);

			if (in_array($value, $currentIds, true))
			{
				// we have nothing to do, its bound already
				return;
			}
			else
			{
				// remove all old bindings and bind this new item
				$bindings = EntityBinding::prepareEntityBindings($this->boundEntityTypeId, [(int)$value]);
			}
		}
		elseif ($this->fieldNameMap->isMultipleIdsFilled() && $commonFieldName === $this->fieldNameMap->getMultipleIds())
		{
			$bindings = EntityBinding::prepareEntityBindings($this->boundEntityTypeId, (array)$value);
		}
		elseif ($commonFieldName === $this->fieldNameMap->getBindings())
		{
			$bindings = (array)$value;
		}
		elseif ($this->fieldNameMap->isBoundEntitiesFilled() && $commonFieldName === $this->fieldNameMap->getBoundEntities())
		{
			$ids = [];
			foreach ((array)$value as $entityToBind)
			{
				if (!($entityToBind instanceof EntityObject))
				{
					$type = is_object($entityToBind) ? $entityToBind::class : gettype($entityToBind);

					throw new ArgumentException(
						'value should an array of ' . EntityObject::class . ", got this as part of the array: {$type}",
					);
				}

				$ids[] = $entityToBind->getId();
			}

			$bindings = EntityBinding::prepareEntityBindings($this->boundEntityTypeId, $ids);
		}
		else
		{
			throw new ArgumentOutOfRangeException('commonFieldName', $this->getHandledFieldNames());
		}

		$this->setBindings($bindings);
	}

	public function isChanged(string $commonFieldName): bool
	{
		$bindingsCollection = $this->getBindingsCollection();
		if (!$bindingsCollection)
		{
			return false;
		}

		if ($bindingsCollection->sysIsChanged())
		{
			return true;
		}

		$scalarFields = $this->bindingEntity->getScalarFields();
		foreach ($bindingsCollection as $bindingObject)
		{
			foreach ($scalarFields as $singleScalarField)
			{
				if ($bindingObject->isChanged($singleScalarField->getName()))
				{
					return true;
				}
			}
		}

		return false;
	}

	public function remindActual(string $commonFieldName)
	{
		return $this->getValue($commonFieldName, Values::ACTUAL);
	}

	public function reset(string $commonFieldName): void
	{
		$this->entityObject->reset($this->fieldNameMap->getBindings());

		$bindingsCollection = $this->getBindingsCollection();
		if (!$bindingsCollection)
		{
			return;
		}

		self::resetAllEntityObjects($bindingsCollection);
	}

	public function unset(string $commonFieldName): void
	{
		$this->entityObject->unset($this->fieldNameMap->getBindings());
	}

	public function getDefaultValue(string $commonFieldName)
	{
		return null;
	}

	public function beforeItemSave(Item $item, EntityObject $entityObject): void
	{
		$actualBindings = $this->remindActual($this->fieldNameMap->getBindings());

		$this->boundIdsBeforeSave = EntityBinding::prepareEntityIDs($this->boundEntityTypeId, $actualBindings);
	}

	public function afterSuccessfulItemSave(Item $item, EntityObject $entityObject): void
	{
	}

	public function save(): Result
	{
		if (is_null($this->boundEntity) || empty($this->thisIdField))
		{
			// everything was saved at entityObject save
			return new Result();
		}

		$newBindings = $this->get($this->fieldNameMap->getBindings());
		$boundIdsAfterSave = EntityBinding::prepareEntityIDs($this->boundEntityTypeId, $newBindings);

		sort($this->boundIdsBeforeSave);
		sort($boundIdsAfterSave);
		if ($boundIdsAfterSave === $this->boundIdsBeforeSave)
		{
			return new Result();
		}

		$affectedIds = array_unique(array_merge($this->boundIdsBeforeSave, $boundIdsAfterSave));
		if (empty($affectedIds))
		{
			return new Result();
		}

		$query = new Query($this->bindingEntity);

		/** @var Collection $collection */
		$collection =
			$query
				->setSelect([$this->boundEntityIdFieldName, $this->thisIdField, 'IS_PRIMARY'])
				->whereIn($this->boundEntityIdFieldName, $affectedIds)
				->fetchCollection()
		;

		/** @var Array<int, array{isPrimary: bool, thisId: int}> $newPrimaryMap */
		$newPrimaryMap = [];

		foreach ($collection as $object)
		{
			$boundEntityId = $object->require($this->boundEntityIdFieldName);
			$thisId = $object->require($this->thisIdField);
			$isPrimary = $object->require('IS_PRIMARY');

			$primaryDesc = $newPrimaryMap[$boundEntityId] ?? null;

			if (
				is_null($primaryDesc)
				|| $isPrimary && $primaryDesc['isPrimary'] === false
				|| $thisId < $primaryDesc['thisId'] && !$primaryDesc['isPrimary']
			)
			{
				$newPrimaryMap[$boundEntityId] = ['isPrimary' => $isPrimary, 'thisId' => $thisId];
			}
		}

		foreach ($affectedIds as $affectedId)
		{
			if (!isset($newPrimaryMap[$affectedId]))
			{
				//no primary found - set to null
				$newPrimaryMap[$affectedId] = ['isPrimary' => false, 'thisId' => null];
			}
		}

		$result = new Result();
		foreach ($newPrimaryMap as $boundEntityId => $primaryDesc)
		{
			$updateResult =  $this->boundEntity->getDataClass()::update(
				$boundEntityId,
				['fields' => [$this->thisIdField => $primaryDesc['thisId']]],
			);

			if (!$updateResult->isSuccess())
			{
				$result->addErrors($updateResult->getErrors());
			}
		}

		if (!$result->getErrors())
		{
			Container::getInstance()->getEntityBroker($this->boundEntityTypeId)?->resetAllCache();
		}

		return $result;
	}

	public function getSerializableFieldNames(): array
	{
		$result = [];
		if ($this->fieldNameMap->isMultipleIdsFilled())
		{
			$result[] = $this->fieldNameMap->getMultipleIds();
		}

		return $result;
	}

	public function getExternalizableFieldNames(): array
	{
		// fields are sorted by priority - more information, higher priority
		$externalizableList = [
			$this->fieldNameMap->getBindings(),
		];

		if ($this->fieldNameMap->isMultipleIdsFilled())
		{
			$externalizableList[] = $this->fieldNameMap->getMultipleIds();
		}
		if ($this->fieldNameMap->isSingleIdFilled())
		{
			$externalizableList[] = $this->fieldNameMap->getSingleId();
		}

		return $externalizableList;
	}

	public function transformToExternalValue(string $commonFieldName, $value, int $valuesType)
	{
		return $value;
	}

	public function setFromExternalValues(array $externalValues): void
	{
		// Only first not empty field is processed
		foreach ($this->getExternalizableFieldNames() as $commonFieldName)
		{
			if (isset($externalValues[$commonFieldName]))
			{
				$this->set($commonFieldName, $externalValues[$commonFieldName]);

				return;
			}
		}
	}

	public function afterItemClone(Item $item, EntityObject $entityObject): void
	{
		$this->entityObject = $entityObject;
	}

	public function getFieldNamesToFill(): array
	{
		$list = [$this->fieldNameMap->getBindings()];

		if ($this->fieldNameMap->isSingleIdFilled())
		{
			$list[] = $this->fieldNameMap->getSingleId();
		}

		return $list;
	}
	//endregion

	//region Get helpers
	private function getValue(string $commonFieldName, int $valuesType)
	{
		if ($this->fieldNameMap->isSingleIdFilled() && $commonFieldName === $this->fieldNameMap->getSingleId())
		{
			if ($valuesType === Values::ACTUAL)
			{
				return $this->entityObject->remindActual($this->fieldNameMap->getSingleId());
			}

			return $this->entityObject->get($this->fieldNameMap->getSingleId());
		}

		if ($valuesType === Values::ACTUAL)
		{
			$bindingsCollection = $this->getActualBindingsCollection();
		}
		else
		{
			$bindingsCollection = $this->getBindingsCollection();
		}

		if (!$bindingsCollection)
		{
			return [];
		}

		$bindings = $this->bindingsCollectionToArray($bindingsCollection);

		if ($this->fieldNameMap->isMultipleIdsFilled() && $commonFieldName === $this->fieldNameMap->getMultipleIds())
		{
			return EntityBinding::prepareEntityIDs($this->boundEntityTypeId, $bindings);
		}
		if ($commonFieldName === $this->fieldNameMap->getBindings())
		{
			return $bindings;
		}
		if ($this->fieldNameMap->isBoundEntitiesFilled() && $commonFieldName === $this->fieldNameMap->getBoundEntities())
		{
			return $this->getBoundEntities($bindings);
		}

		throw new ArgumentOutOfRangeException('commonFieldName', $this->getHandledFieldNames());
	}

	/**
	 * Mostly redundant method, but is used for type hinting
	 *
	 * @return Collection|null
	 */
	private function getBindingsCollection(): ?Collection
	{
		return $this->entityObject->get($this->fieldNameMap->getBindings());
	}

	private function getActualBindingsCollection(): ?Collection
	{
		$collection = $this->getBindingsCollection();
		if (!$collection)
		{
			return null;
		}

		$collection = clone $collection;
		if ($collection->sysIsChanged())
		{
			$collection->sysResetChanges(true);
		}

		self::resetAllEntityObjects($collection);

		return $collection;
	}

	private static function resetAllEntityObjects(Collection $collection): void
	{
		$scalarFields = $collection->entity->getScalarFields();
		foreach ($collection as $object)
		{
			foreach ($scalarFields as $singleScalarField)
			{
				$object->reset($singleScalarField->getName());
			}
		}
	}

	private function bindingsCollectionToArray(Collection $bindingsCollection): array
	{
		$bindings = [];
		foreach ($bindingsCollection as $bindingObject)
		{
			$bindings[] = [
				$this->boundEntityIdFieldName => $bindingObject->get($this->boundEntityIdFieldName),
				'SORT' => $bindingObject->get('SORT'),
				'ROLE_ID' => $bindingObject->get('ROLE_ID'),
			];
		}

		foreach ($bindingsCollection as $bindingObject)
		{
			if ($bindingObject->get('IS_PRIMARY'))
			{
				$primary = $bindingObject;
			}
		}

		if (isset($primary))
		{
			EntityBinding::markAsPrimary(
				$bindings,
				$this->boundEntityTypeId,
				$primary->get($this->boundEntityIdFieldName)
			);
		}

		sortByColumn($bindings, ['SORT' => SORT_ASC]);

		return $bindings;
	}

	private function getBoundEntities(array $bindings): array
	{
		$ids = EntityBinding::prepareEntityIDs($this->boundEntityTypeId, $bindings);
		$entities = $this->broker->getBunchByIds($ids);

		$sorts = [];
		foreach ($bindings as $singleBinding)
		{
			$entityId = EntityBinding::prepareEntityID($this->boundEntityTypeId, $singleBinding);
			$sorts[$entityId] = (int)($singleBinding['SORT'] ?? 0);
		}

		usort(
			$entities,
			static function (EntityObject $left, EntityObject $right) use ($sorts): int {
				$sortLeft = (int)($sorts[$left->getId()] ?? 0);
				$sortRight = (int)($sorts[$right->getId()] ?? 0);

				return ($sortLeft - $sortRight);
			}
		);

		return $entities;
	}
	//endregion

	//region Set helpers
	private function setBindings(array $bindings): void
	{
		EntityBinding::normalizeEntityBindings($this->boundEntityTypeId, $bindings);
		EntityBinding::removeBindingsWithDuplicatingEntityIDs($this->boundEntityTypeId, $bindings);

		$bindingsCollection = $this->getBindingsCollection();
		$currentBindings = $bindingsCollection ? $this->bindingsCollectionToArray($bindingsCollection) : [];

		[$add, $update, $delete] = $this->separateBindingsByOperation($currentBindings, $bindings);

		$this->addBindings($add);

		// may be bindings collection was created in entityObject while we were adding new bindings to it
		$bindingsCollection = $bindingsCollection ? $bindingsCollection : $this->getBindingsCollection();
		if ($bindingsCollection)
		{
			$this->updateBindings($bindingsCollection, $update);
			$this->deleteBindings($bindingsCollection, $delete);
		}

		if (!$bindingsCollection || count($bindingsCollection) <= 0)
		{
			if ($this->fieldNameMap->isSingleIdFilled())
			{
				$this->entityObject->set($this->fieldNameMap->getSingleId(), 0);
			}

			return;
		}

		$idOfPrimaryBoundEntity = $this->ensureExactlyOnePrimaryBoundEntityExists($bindingsCollection, $bindings);

		if ($this->fieldNameMap->isSingleIdFilled())
		{
			$this->entityObject->set($this->fieldNameMap->getSingleId(), $idOfPrimaryBoundEntity);
		}
	}

	/**
	 * @param array $currentBindings
	 * @param array $providedBindings
	 * @return Array<mixed, Array<int, Array<string, mixed>>>
	 */
	private function separateBindingsByOperation(array $currentBindings, array $providedBindings): array
	{
		$add = [];
		$update = [];
		$delete = [];

		foreach ($providedBindings as $singleProvidedBinding)
		{
			$entityId = EntityBinding::prepareEntityID($this->boundEntityTypeId, $singleProvidedBinding);

			$isSimilarCurrentBindingExists =
				EntityBinding::findBindingByEntityID($this->boundEntityTypeId, $entityId, $currentBindings)
			;
			if ($isSimilarCurrentBindingExists)
			{
				$update[$entityId] = $singleProvidedBinding;
			}
			else
			{
				$add[$entityId] = $singleProvidedBinding;
			}
		}

		foreach ($currentBindings as $singleCurrentBinding)
		{
			$entityId = EntityBinding::prepareEntityID($this->boundEntityTypeId, $singleCurrentBinding);
			if (!isset($add[$entityId]) && !isset($update[$entityId]))
			{
				$delete[$entityId] = $singleCurrentBinding;
			}
		}

		return [$add, $update, $delete];
	}

	private function addBindings(array $bindingsToAdd): void
	{
		foreach ($bindingsToAdd as $singleBindingToAdd)
		{
			/** @var EntityObject $bindingObject */
			$bindingObject = $this->bindingEntity->createObject();
			foreach (($singleBindingToAdd + $this->defaultValuesForBindingObject) as $fieldName => $value)
			{
				if ($this->bindingEntity->hasField($fieldName))
				{
					$bindingObject->set($fieldName, $value);
				}
			}

			$this->entityObject->addTo($this->fieldNameMap->getBindings(), $bindingObject);
		}
	}

	private function updateBindings(Collection $bindingsCollection, array $bindingsToUpdate): void
	{
		foreach ($bindingsToUpdate as $singleBindingToUpdate)
		{
			$bindingObject = $this->findBindingObject($bindingsCollection, $singleBindingToUpdate);
			if (!$bindingObject)
			{
				throw new ObjectNotFoundException('Could not find binding to update');
			}

			foreach ($singleBindingToUpdate as $fieldName => $value)
			{
				if ($this->bindingEntity->hasField($fieldName) && !$this->bindingEntity->getField($fieldName)->isPrimary())
				{
					$bindingObject->set($fieldName, $value);
				}
			}
		}
	}

	private function deleteBindings(Collection $bindingsCollection, array $bindingsToDelete): void
	{
		foreach ($bindingsToDelete as $singleBindingToDelete)
		{
			$bindingObject = $this->findBindingObject($bindingsCollection, $singleBindingToDelete);
			if (!$bindingObject)
			{
				throw new ObjectNotFoundException('Could not find binding to delete');
			}

			$this->entityObject->removeFrom($this->fieldNameMap->getBindings(), $bindingObject);
		}
	}

	private function findBindingObject(Collection $bindingsCollection, array $bindingToFind): ?EntityObject
	{
		$boundEntityId = EntityBinding::prepareEntityID($this->boundEntityTypeId, $bindingToFind);

		foreach ($bindingsCollection as $bindingObject)
		{
			if ($bindingObject->get($this->boundEntityIdFieldName) === $boundEntityId)
			{
				return $bindingObject;
			}
		}

		return null;
	}

	private function ensureExactlyOnePrimaryBoundEntityExists(Collection $bindingsCollection, array $providedBindings): int
	{
		$idOfPrimaryBoundEntity = $this->selectPrimaryBoundEntity($bindingsCollection, $providedBindings);

		foreach ($bindingsCollection as $bindingObject)
		{
			if ($bindingObject->get($this->boundEntityIdFieldName) === $idOfPrimaryBoundEntity)
			{
				$bindingObject->set('IS_PRIMARY', true);
			}
			else
			{
				$bindingObject->set('IS_PRIMARY', false);
			}
		}

		return $idOfPrimaryBoundEntity;
	}

	private function selectPrimaryBoundEntity(Collection $bindingsCollection, array $providedBindings): int
	{
		$idOfPrimaryBoundEntity = null;

		$primaryBinding = EntityBinding::findPrimaryBinding($providedBindings);
		if ($primaryBinding)
		{
			$idOfPrimaryBoundEntity = EntityBinding::prepareEntityID($this->boundEntityTypeId, $primaryBinding);
			// prioritize explicitly set primary binding
			if ($idOfPrimaryBoundEntity > 0)
			{
				return $idOfPrimaryBoundEntity;
			}
		}

		$firstBindingObject = null;
		// let the current primary binding to remain primary
		foreach ($bindingsCollection as $bindingObject)
		{
			if (!$firstBindingObject)
			{
				$firstBindingObject = $bindingObject;
			}

			if ($bindingObject->get('IS_PRIMARY'))
			{
				$idOfPrimaryBoundEntity = $bindingObject->get($this->boundEntityIdFieldName);

				break;
			}
		}

		if ($idOfPrimaryBoundEntity <= 0)
		{
			// if no primary found, simply make the first binding primary
			$idOfPrimaryBoundEntity = $firstBindingObject->get($this->boundEntityIdFieldName);
		}

		if ($idOfPrimaryBoundEntity <= 0)
		{
			throw new InvalidOperationException('Could not select a primary bound entity, which should be an impossible case');
		}

		return $idOfPrimaryBoundEntity;
	}
	//endregion
}
