<?php

namespace Bitrix\Crm\Service;

use Bitrix\Crm\FieldMultiTable;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Multifield;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Error;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\Result;
use CCrmFieldMulti;

class MultifieldStorage
{
	/** @var FieldMultiTable */
	private $dataManager = FieldMultiTable::class;
	/** @var Array<string, Multifield\Collection> */
	private array $cache = [];

	private CCrmFieldMulti $fieldMulti;
	private \CMain $compatibleApplication;

	final public function __construct()
	{
		$this->fieldMulti = new CCrmFieldMulti();

		global $APPLICATION;
		$this->compatibleApplication = $APPLICATION;
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
		$extraData = $this->fetchExtraData($owner);

		$collection = new Multifield\Collection();
		while ($row = $result->fetch())
		{
			$value = Multifield\Assembler::valueByDatabaseRow($row);
			if (isset($extraData[$value->getId()]))
			{
				$value->setValueExtra((new Multifield\ValueExtra())->setCountryCode($extraData[$value->getId()]));
			}

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
			$owner = new ItemIdentifier($entityTypeId, $ownerId);
			$extraData = $this->fetchExtraData($owner);

			$collection = $collections[$ownerId] ?? null;
			if (!$collection)
			{
				$collection = new Multifield\Collection();
				$collections[$ownerId] = $collection;
			}

			$value = Multifield\Assembler::valueByDatabaseRow($row);
			if (isset($extraData[$value->getId()]))
			{
				$value->setValueExtra((new Multifield\ValueExtra())->setCountryCode($extraData[$value->getId()]));
			}

			$collection->add($value);
		}

		return $collections;
	}

	private function fetchExtraData(ItemIdentifier $owner): array
	{
		$phoneIds = $this->dataManager::fetchPhoneIdsByOwner($owner);
		if (empty($phoneIds))
		{
			return [];
		}

		return CCrmFieldMulti::GetPhoneCountryList($phoneIds);
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

	final public function validate(Multifield\Collection $values): Result
	{
		$result = new Result();

		foreach ($values as $value)
		{
			$singleResult = $this->validateSingle($value);
			if (!$singleResult->isSuccess())
			{
				$result->addErrors($singleResult->getErrors());
			}
		}

		return $result;
	}

	private function validateSingle(Multifield\Value $value): Result
	{
		$result = new Result();

		$valueToValidate = clone $value;
		if ($valueToValidate->getValue() === null)
		{
			// hack: 'CheckFields' will not throw error if value is not set, but it's still an error
			$valueToValidate->setValue('');
		}

		$isSuccess = $this->fieldMulti->CheckFields(Multifield\Assembler::databaseRowByValue($valueToValidate));
		if (!$isSuccess)
		{
			$result->addError($this->getErrorFromApplication(['value' => $value]));
		}

		return $result;
	}

	private function getErrorFromApplication(?array $customData = null): Error
	{
		return new Error((string)$this->compatibleApplication->GetException(), 0, $customData);
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
		$result = new Result();

		$actualValues = $this->get($owner);
		if ($actualValues->isEqualTo($values))
		{
			return $result;
		}

		[$toAdd, $toUpdate, $toDelete] = $this->separateValuesBySaveOperation($actualValues, $values);

		foreach ($toAdd as $value)
		{
			$singleValidateResult = $this->validateSingle($value);
			if (!$singleValidateResult->isSuccess())
			{
				$result->addErrors($singleValidateResult->getErrors());

				$toAdd->remove($value);
			}
		}

		foreach ($toUpdate as $value)
		{
			$singleValidateResult = $this->validateSingle($value);
			if ($value->getId() <= 0)
			{
				$singleValidateResult->addError(
					new Error('Cant update value without ID', 0, ['value' => $value])
				);
			}

			if (!$singleValidateResult->isSuccess())
			{
				$result->addErrors($singleValidateResult->getErrors());

				$toUpdate->remove($value);
			}
		}

		$saveResult = $this->saveToDb($owner, $toAdd, $toUpdate, $toDelete);
		if (!$saveResult->isSuccess())
		{
			$result->addErrors($saveResult->getErrors());
		}

		$this->clearCache($owner);

		return $result;
	}

	/**
	 * @param Multifield\Collection $oldValues
	 * @param Multifield\Collection $newValues
	 *
	 * @return array{
	 *     0: Multifield\Collection,
	 *     1: Multifield\Collection,
	 *     2: Multifield\Collection
	 * }
	 */
	private function separateValuesBySaveOperation(
		Multifield\Collection $oldValues,
		Multifield\Collection $newValues,
	): array
	{
		$add = new Multifield\Collection();
		$update = new Multifield\Collection();
		$delete = new Multifield\Collection();

		$deduplicatedNewValues = $this->deduplicateCollection($newValues);

		foreach ($deduplicatedNewValues as $newValue)
		{
			$oldValue = $newValue->getId() > 0 ? $oldValues->getById($newValue->getId()) : null;
			if ($oldValue && !$oldValue->isEqualTo($newValue))
			{
				$update->add($newValue);
			}
			elseif (!$oldValue)
			{
				$add->add($newValue);
			}
		}

		foreach ($oldValues as $oldValue)
		{
			if (!$deduplicatedNewValues->getById($oldValue->getId()))
			{
				$delete->add($oldValue);
			}
		}

		return [$add, $update, $delete];
	}

	/**
	 * Even though Collection doesn't allow duplicates addition, someone still can modify
	 * an existing value in a collection to make it a duplicate
	 *
	 * @param Multifield\Collection $collection
	 *
	 * @return Multifield\Collection
	 */
	private function deduplicateCollection(
		Multifield\Collection $collection,
	): Multifield\Collection
	{
		$deduplicated = new Multifield\Collection();

		$allValues = $collection->getAll();
		// values with id > 0 should be added first, as they are already saved to the DB
		usort($allValues, function (Multifield\Value $left, Multifield\Value $right) {
			return (int)$left->getId() <=> (int)$right->getId();
		});

		foreach ($allValues as $value)
		{
			// Collection::add doesn't allow adding duplicates. The new collection will contain only unique values
			$deduplicated->add($value);
		}

		return $deduplicated;
	}

	private function saveToDb(
		ItemIdentifier $owner,
		Multifield\Collection $toAdd,
		Multifield\Collection $toUpdate,
		Multifield\Collection $toDelete
	): Result
	{
		$toAddCompatibleArray = [];
		foreach ($toAdd as $value)
		{
			$toAddCompatibleArray[] = Multifield\Assembler::databaseRowByValue($value);
		}

		$toUpdateCompatibleArray = [];
		foreach ($toUpdate as $value)
		{
			$toUpdateCompatibleArray[$value->getId()] = Multifield\Assembler::databaseRowByValue($value);
		}

		//todo move saving and validation logic to FieldMultiTable
		return $this->fieldMulti->saveBulk(
			\CCrmOwnerType::ResolveName($owner->getEntityTypeId()),
			$owner->getEntityId(),
			$toAddCompatibleArray,
			$toUpdateCompatibleArray,
			array_map(fn(Multifield\Value $value) => $value->getId(), $toDelete->getAll()),
		);
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
	final protected function setFieldMulti(CCrmFieldMulti $fieldMulti): self
	{
		$this->fieldMulti = $fieldMulti;

		return $this;
	}
}
