<?php

namespace Bitrix\Crm\Item\FieldImplementation;

use Bitrix\Crm\Entity\CommentsHelper;
use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Item\FieldImplementation;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Model\FieldContentTypeTable;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\Result;

final class Comments implements FieldImplementation
{
	private Field\Collection $fields;
	private EntityObject $entityObject;
	private Item $item;

	public function __construct(Item $item, EntityObject $object, Field\Collection $fieldsToHandle)
	{
		$this->fields = $fieldsToHandle;
		$this->entityObject = $object;
		$this->item = $item;
	}

	public function getHandledFieldNames(): array
	{
		return $this->fields->getFieldNameList();
	}

	public function get(string $commonFieldName)
	{
		return $this->convertToBBIfNeeded($commonFieldName, $this->entityObject->get($commonFieldName));
	}

	private function convertToBBIfNeeded(string $commonFieldName, $value)
	{
		if ($this->item->isNew())
		{
			return $value;
		}

		$contentTypeId = FieldContentTypeTable::getContentTypeId(
			ItemIdentifier::createByItem($this->item),
			$commonFieldName
		);

		if ($contentTypeId !== \CCrmContentType::BBCode)
		{
			$value = CommentsHelper::normalizeComment($value);
		}

		return $value;
	}

	public function set(string $commonFieldName, $value): void
	{
		$this->entityObject->set($commonFieldName, $value);
	}

	public function isChanged(string $commonFieldName): bool
	{
		return $this->entityObject->isChanged($commonFieldName);
	}

	public function remindActual(string $commonFieldName)
	{
		return $this->convertToBBIfNeeded($commonFieldName, $this->entityObject->remindActual($commonFieldName));
	}

	public function reset(string $commonFieldName): void
	{
		$this->entityObject->reset($commonFieldName);
	}

	public function unset(string $commonFieldName): void
	{
		$this->entityObject->unset($commonFieldName);
	}

	public function getDefaultValue(string $commonFieldName)
	{
		return $this->entityObject->entity->getField($commonFieldName)->getDefaultValue();
	}

	public function beforeItemSave(Item $item, EntityObject $entityObject): void
	{
	}

	public function afterSuccessfulItemSave(Item $item, EntityObject $entityObject): void
	{
		$this->afterItemClone($item, $entityObject);
	}

	public function save(): Result
	{
		// everything was saved on item save
		return new Result();
	}

	public function getSerializableFieldNames(): array
	{
		return $this->getHandledFieldNames();
	}

	public function getExternalizableFieldNames(): array
	{
		return $this->getHandledFieldNames();
	}

	public function transformToExternalValue(string $commonFieldName, $value, int $valuesType)
	{
		return $value;
	}

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
		$this->item = $item;
		$this->entityObject = $entityObject;
	}

	public function getFieldNamesToFill(): array
	{
		return [];
	}
}
