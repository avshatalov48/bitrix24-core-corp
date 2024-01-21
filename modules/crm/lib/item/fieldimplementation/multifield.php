<?php

namespace Bitrix\Crm\Item\FieldImplementation;

use Bitrix\Crm\Comparer\MultifieldComparer;
use Bitrix\Crm\Item;
use Bitrix\Crm\Item\FieldImplementation;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Multifield\Assembler;
use Bitrix\Crm\Multifield\Collection;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\ORM\Objectify\Values;
use Bitrix\Main\Result;

final class Multifield implements FieldImplementation
{
	/** @var int */
	private $ownerEntityTypeId;
	/** @var int */
	private $ownerId;

	/** @var Collection */
	private $actual;
	/** @var Collection|null */
	private $current;

	public function __construct(int $ownerEntityTypeId, int $ownerId)
	{
		$this->ownerEntityTypeId = $ownerEntityTypeId;
		$this->ownerId = $ownerId;
	}

	public function getHandledFieldNames(): array
	{
		return [Item::FIELD_NAME_FM];
	}

	private function load(): void
	{
		if (!$this->actual)
		{
			$multifields = new Collection();

			if ($this->ownerId > 0)
			{
				$storage = Container::getInstance()->getMultifieldStorage();

				$multifields = $storage->get(new ItemIdentifier($this->ownerEntityTypeId, $this->ownerId));
			}

			$this->actual = $multifields;
		}
	}

	public function get(string $commonFieldName)
	{
		$this->load();

		$multifields = $this->current ?? $this->actual;

		return clone $multifields;
	}

	public function set(string $commonFieldName, $value): void
	{
		$this->load();

		if (!($value instanceof Collection))
		{
			throw new ArgumentTypeException('value', Collection::class);
		}

		if ($this->actual->isEqualTo($value))
		{
			$this->current = null;
		}
		else
		{
			$this->current = clone $value;
		}
	}

	public function isChanged(string $commonFieldName): bool
	{
		if (!$this->current)
		{
			return false;
		}

		$this->load();

		return !$this->current->isEqualTo($this->actual);
	}

	public function remindActual(string $commonFieldName)
	{
		$this->load();

		return clone $this->actual;
	}

	public function reset(string $commonFieldName): void
	{
		$this->current = null;
	}

	public function unset(string $commonFieldName): void
	{
		$this->actual = null;
		$this->current = null;
	}

	public function getDefaultValue(string $commonFieldName)
	{
		return null;
	}

	public function beforeItemSave(Item $item, EntityObject $entityObject): void
	{
	}

	public function afterSuccessfulItemSave(Item $item, EntityObject $entityObject): void
	{
		$this->ownerEntityTypeId = $item->getEntityTypeId();
		$this->ownerId = $item->getId();
	}

	public function save(): Result
	{
		$fieldName = $this->getHandledFieldNames()[0];

		if (!$this->isChanged($fieldName))
		{
			return new Result();
		}

		$storage = Container::getInstance()->getMultifieldStorage();
		$identifier = new ItemIdentifier($this->ownerEntityTypeId, $this->ownerId);

		$result = $storage->save($identifier, $this->get($fieldName));
		if ($result->isSuccess())
		{
			$this->actual = $storage->get($identifier);
			$this->current = null;
		}

		return $result;
	}

	public function getSerializableFieldNames(): array
	{
		return [];
	}

	public function getExternalizableFieldNames(): array
	{
		return $this->getHandledFieldNames();
	}

	public function transformToExternalValue(string $commonFieldName, $value, int $valuesType)
	{
		if (!($value instanceof Collection))
		{
			throw new ArgumentTypeException('value', Collection::class);
		}

		if ($valuesType === Values::ACTUAL)
		{
			return $value->toArray();
		}

		$current = $value;

		$comparer = new MultifieldComparer();

		return $comparer->getChangedCompatibleArray($this->remindActual($commonFieldName), $current);
	}

	public function setFromExternalValues(array $externalValues): void
	{
		$commonFieldName = $this->getHandledFieldNames()[0];

		$externalValue = $externalValues[$commonFieldName] ?? null;
		if (!is_null($externalValue))
		{
			$current = $this->get($commonFieldName);

			Assembler::updateCollectionByArray($current, (array)$externalValue);

			$this->set($commonFieldName, $current);
		}
	}

	public function afterItemClone(Item $item, EntityObject $entityObject): void
	{
		$this->afterSuccessfulItemSave($item, $entityObject);
	}

	public function getFieldNamesToFill(): array
	{
		return [];
	}
}
