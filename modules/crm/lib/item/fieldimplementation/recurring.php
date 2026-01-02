<?php

namespace Bitrix\Crm\Item\FieldImplementation;

use Bitrix\Crm\Item;
use Bitrix\Crm\Item\FieldImplementation;
use Bitrix\Crm\Model\Dynamic\RecurringTable;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\Result;

class Recurring implements FieldImplementation
{
	private int $ownerEntityTypeId;
	private int $ownerId;
	private $actual;
	private $current;
	private ?Item $item = null;

	public function __construct(int $ownerEntityTypeId, int $ownerId)
	{
		$this->ownerEntityTypeId = $ownerEntityTypeId;
		$this->ownerId = $ownerId;
	}

	public function getHandledFieldNames(): array
	{
		return [Item::FIELD_NAME_RECURRING];
	}

	private function load(): void
	{
		if ($this->actual)
		{
			return;
		}

		$this->actual = RecurringTable::getList([
			'filter' => [
				'=ENTITY_TYPE_ID' => $this->ownerEntityTypeId,
				'=ITEM_ID' => $this->ownerId,
			],
			'limit' => 1,
		])->fetch();
	}

	public function get(string $commonFieldName)
	{
		$this->load();

		return $this->current ?? $this->actual;
	}

	public function set(string $commonFieldName, $value): void
	{
		$this->load();

		if ($this->actual === $value)
		{
			$this->current = null;
		}
		else
		{
			$this->current = $value;
		}
	}

	public function isChanged(string $commonFieldName): bool
	{
		if (!$this->current)
		{
			return false;
		}

		$this->load();

		return $this->current !== $this->actual;
	}

	public function remindActual(string $commonFieldName)
	{
		$this->load();

		return $this->actual;
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
		return new Result();
	}

	public function getSerializableFieldNames(): array
	{
		return ['RECURRING'];
	}

	public function getExternalizableFieldNames(): array
	{
		return $this->getHandledFieldNames();
	}

	// @todo **recurring support later
	public function transformToExternalValue(string $commonFieldName, $value, int $valuesType)
	{
		return $value;
	}

	// @todo **recurring support later
	public function setFromExternalValues(array $externalValues): void
	{
		foreach ($this->getHandledFieldNames() as $fieldName)
		{
			if (isset($externalValues[$fieldName]))
			{
				$this->set($fieldName, $externalValues[$fieldName]);
			}
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
