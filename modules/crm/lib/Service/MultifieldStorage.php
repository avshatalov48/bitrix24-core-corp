<?php

namespace Bitrix\Crm\Service;

use Bitrix\Crm\FieldMultiTable;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Multifield;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Error;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\Result;

class MultifieldStorage
{
	/** @var FieldMultiTable */
	private $dataManager = FieldMultiTable::class;
	/** @var Array<string, Multifield\Collection> */
	private $cache = [];

	/** @var \CCrmFieldMulti */
	private $fieldMulti;

	final public function __construct()
	{
		$this->fieldMulti = new \CCrmFieldMulti();
	}

	final public function get(ItemIdentifier $owner): Multifield\Collection
	{
		$collection = $this->getFromCache($owner);
		if (!$collection)
		{
			$collection = $this->fetch($owner);
			$this->addToCache($owner, $collection);
		}

		return $collection;
	}

	private function getFromCache(ItemIdentifier $owner): ?Multifield\Collection
	{
		if (isset($this->cache[$owner->getHash()]))
		{
			return clone $this->cache[$owner->getHash()];
		}

		return null;
	}

	private function addToCache(ItemIdentifier $owner, Multifield\Collection $collection): void
	{
		$this->cache[$owner->getHash()] = clone $collection;
	}

	private function clearCache(ItemIdentifier $owner): void
	{
		unset($this->cache[$owner->getHash()]);
	}

	private function fetch(ItemIdentifier $owner): Multifield\Collection
	{
		$result = $this->dataManager::fetchByOwner($owner);

		$collection = new Multifield\Collection();
		while ($row = $result->fetch())
		{
			$value = Multifield\Assembler::valueByDatabaseRow($row);
			$collection->add($value);
		}

		return $collection;
	}

	private function fetchForMultipleOwners(int $entityTypeId, array $ownerIds): array
	{
		$result = $this->dataManager::fetchByMultipleOwners($entityTypeId, $ownerIds);

		$collections = [];
		while ($row = $result->fetch())
		{
			$ownerId = Multifield\Assembler::extractOwnerId($row);

			$collection = $collections[$ownerId] ?? null;
			if (!$collection)
			{
				$collection = new Multifield\Collection();
				$collections[$ownerId] = $collection;
			}

			$value = Multifield\Assembler::valueByDatabaseRow($row);
			$collection->add($value);
		}

		return $collections;
	}

	/**
	 * @param int $entityTypeId
	 * @param int[] $ownerIds
	 * @return Multifield\Collection[]
	 */
	final public function getForMultipleOwners(int $entityTypeId, array $ownerIds): array
	{
		\Bitrix\Main\Type\Collection::normalizeArrayValuesByInt($ownerIds);
		if (empty($ownerIds))
		{
			return [];
		}
		if (!\CCrmOwnerType::IsDefined($entityTypeId))
		{
			return [];
		}

		$result = [];
		$ownerIdsToFetch = [];
		foreach ($ownerIds as $ownerId)
		{
			$owner = new ItemIdentifier($entityTypeId, $ownerId);
			$collection = $this->getFromCache($owner);
			if ($collection)
			{
				$result[$owner->getEntityId()] = $collection;
			}
			else
			{
				$ownerIdsToFetch[] = $owner->getEntityId();
			}
		}

		if (empty($ownerIdsToFetch))
		{
			return $result;
		}

		foreach ($this->fetchForMultipleOwners($entityTypeId, $ownerIdsToFetch) as $ownerId => $collection)
		{
			$owner = new ItemIdentifier($entityTypeId, $ownerId);
			$this->addToCache($owner, $collection);

			$result[$owner->getEntityId()] = $collection;
		}

		return $result;
	}

	/**
	 * @internal Use the getter instead. For internal system usage only
	 * @see MultifieldStorage::getForMultipleOwners()
	 *
	 * @param int $entityTypeId
	 * @param int[] $ownerIds
	 * @return void
	 */
	final public function warmupCache(int $entityTypeId, array $ownerIds): void
	{
		$this->getForMultipleOwners($entityTypeId, $ownerIds);
	}

	/**
	 * Saves multifields values
	 *
	 * @param ItemIdentifier $owner
	 * @param Multifield\Collection $values - all multifields values should be provided. Values that are not provided
	 * will be deleted
	 * @return Result
	 */
	final public function save(ItemIdentifier $owner, Multifield\Collection $values): Result
	{
		$actualCollection = $this->get($owner);

		$valuesToSave = clone $values;

		$result = $this->prepareValues($actualCollection, $valuesToSave);

		//todo move saving logic from compatible class to this method. And then call this method in a compatible class
		$this->fieldMulti->SetFields(
			\CCrmOwnerType::ResolveName($owner->getEntityTypeId()),
			$owner->getEntityId(),
			$valuesToSave->toArray(),
		);

		$this->clearCache($owner);

		return $result;
	}

	/**
	 * @todo remove it. validation and normalization logic should be moved to FieldsMultiTable
	 */
	private function prepareValues(
		Multifield\Collection $actualValues,
		Multifield\Collection $valuesToSave
	): Result
	{
		global $APPLICATION;

		$result = new Result();
		$invalidValues = [];
		foreach ($valuesToSave as $valueToSave)
		{
			if (is_null($valueToSave->getValue()))
			{
				$valueToSave->setValue('');
			}

			$isSuccess = $this->fieldMulti->CheckFields(Multifield\Assembler::databaseRowByValue($valueToSave));
			if (!$isSuccess)
			{
				$result->addError(new Error((string)$APPLICATION->GetException()));

				$invalidValues[] = $valueToSave;
			}
		}

		foreach ($actualValues as $actualValue)
		{
			if (!$valuesToSave->getById($actualValue->getId()))
			{
				// delete value that was removed from actual collection
				$actualValue->setValue('');
				$valuesToSave->add($actualValue);
			}
		}

		foreach ($invalidValues as $invalidValue)
		{
			//do not save (skip) invalid values
			$valuesToSave->remove($invalidValue);
		}

		return $result;
	}

	/**
	 * This method is used for testing purposes. Do not use it in your code. It is not covered by backwards compatibility
	 *
	 * @internal
	 */
	final protected function setDataManager(string $dataManager): self
	{
		if (!is_a($dataManager, DataManager::class, true))
		{
			throw new ArgumentTypeException('dataManager', DataManager::class);
		}

		$this->dataManager = $dataManager;

		return $this;
	}

	/**
	 * This method is used for testing purposes. Do not use it in your code. It is not covered by backwards compatibility
	 *
	 * @internal
	 */
	final protected function setFieldMulti(\CCrmFieldMulti $fieldMulti): self
	{
		$this->fieldMulti = $fieldMulti;

		return $this;
	}
}
