<?php

namespace Bitrix\Crm\Item\FieldImplementation;

use Bitrix\Crm\Entity\FieldContentType;
use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Item\FieldImplementation;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Model\FieldContentTypeTable;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\Result;

final class ContentTypeId implements FieldImplementation
{
	private Field\Collection $fields;
	private Item $item;
	/** @var Array<string, int>|null */
	private ?array $actual = null;
	/** @var Array<string, int> */
	private array $current = [];

	private ?array $handledFieldNamesCache = null;

	public function __construct(Item $item, Field\Collection $fieldsToHandle)
	{
		$this->item = $item;
		$this->fields = $fieldsToHandle;

		if ($this->item->isNew())
		{
			foreach ($fieldsToHandle as $field)
			{
				$contentTypeIdFieldName = FieldContentType::compileContentTypeIdFieldName($field->getName());

				$this->current[$contentTypeIdFieldName] = $this->getDefaultValue($contentTypeIdFieldName);
			}
		}
	}

	public function getHandledFieldNames(): array
	{
		if (is_null($this->handledFieldNamesCache))
		{
			$this->handledFieldNamesCache = array_map(
				fn(string $fieldName) => FieldContentType::compileContentTypeIdFieldName($fieldName),
				$this->fields->getFieldNameList(),
			);
		}

		return $this->handledFieldNamesCache;
	}

	public function get(string $commonFieldName)
	{
		$this->assertFieldIsHandled($commonFieldName);

		$this->load();

		return $this->current[$commonFieldName] ?? $this->actual[$commonFieldName] ?? null;
	}

	public function set(string $commonFieldName, $value): void
	{
		$this->assertFieldIsHandled($commonFieldName);

		$this->load();

		if (isset($this->actual[$commonFieldName]) && $this->actual[$commonFieldName] === $value)
		{
			unset($this->current[$commonFieldName]);
		}
		else
		{
			$this->current[$commonFieldName] = (int)$value;
		}
	}

	public function isChanged(string $commonFieldName): bool
	{
		$this->assertFieldIsHandled($commonFieldName);

		if (!isset($this->current[$commonFieldName]))
		{
			return false;
		}

		$this->load();

		return (
			!isset($this->actual[$commonFieldName])
			|| $this->actual[$commonFieldName] !== $this->current[$commonFieldName]
		);
	}

	public function remindActual(string $commonFieldName)
	{
		$this->assertFieldIsHandled($commonFieldName);

		$this->load();

		return $this->actual[$commonFieldName] ?? null;
	}

	public function reset(string $commonFieldName): void
	{
		$this->assertFieldIsHandled($commonFieldName);

		unset($this->current[$commonFieldName]);
	}

	public function unset(string $commonFieldName): void
	{
		$this->assertFieldIsHandled($commonFieldName);

		unset($this->actual[$commonFieldName], $this->current[$commonFieldName]);
	}

	public function getDefaultValue(string $commonFieldName)
	{
		return FieldContentTypeTable::getDefaultContentTypeId();
	}

	public function afterSuccessfulItemSave(Item $item, EntityObject $entityObject): void
	{
		$this->item = $item;
	}

	public function save(): Result
	{
		if (empty($this->current))
		{
			return new Result();
		}

		$mapToSave = [];
		foreach ($this->current as $contentTypeIdFieldName => $contentTypeId)
		{
			$mapToSave[FieldContentType::compileRegularFieldName($contentTypeIdFieldName)] = $contentTypeId;
		}

		$result = FieldContentTypeTable::saveForItem(ItemIdentifier::createByItem($this->item), $mapToSave);
		if ($result->isSuccess())
		{
			// load again, so data is consistent with the DB
			$this->actual = null;
			$this->current = [];
		}

		return $result;
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
		foreach ($externalValues as $fieldName => $value)
		{
			if (in_array($fieldName, $this->getHandledFieldNames(), true))
			{
				$this->set($fieldName, $value);
			}
		}
	}

	public function afterItemClone(Item $item, EntityObject $entityObject): void
	{
		$this->item = $item;
	}

	public function getFieldNamesToFill(): array
	{
		return [];
	}

	private function assertFieldIsHandled(string $commonFieldName): void
	{
		if (!in_array($commonFieldName, $this->getHandledFieldNames(), true))
		{
			throw new ArgumentOutOfRangeException('commonFieldName', $this->getHandledFieldNames());
		}
	}

	private function load(): void
	{
		if (!is_null($this->actual))
		{
			return;
		}

		$this->actual = [];

		if (!$this->item->isNew())
		{
			$fieldToContentTypeId = FieldContentTypeTable::loadForItem(ItemIdentifier::createByItem($this->item));

			foreach ($this->fields as $field)
			{
				$contentTypeId = $fieldToContentTypeId[$field->getName()] ?? FieldContentTypeTable::getDefaultContentTypeId();
				$this->actual[FieldContentType::compileContentTypeIdFieldName($field->getName())] = $contentTypeId;
			}
		}
	}
}
