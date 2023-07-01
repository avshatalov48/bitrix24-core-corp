<?php

namespace Bitrix\Rpa\UserField;

use Bitrix\Rpa\Model\FieldTable;

class UserFieldCollection implements \Iterator, \ArrayAccess, \Countable
{
	/** @var UserField[] */
	protected $fields = [];

	public function __construct(array $fields, array $visibility = [], array $defaultVisibility = null)
	{
		if($defaultVisibility === null)
		{
			$defaultVisibility = static::getDefaultVisibility();
		}
		foreach($fields as $field)
		{
			$userField = new UserField($field, $this->getFieldVisibility($field['FIELD_NAME'], $visibility, $defaultVisibility));
			$this->fields[$userField->getName()] = $userField;
		}
	}

	public function getByName(string $fieldName): ?UserField
	{
		return $this->fields[$fieldName] ?? null;
	}

	public function toArray(): array
	{
		$fields = [];
		foreach($this->fields as $field)
		{
			$fields[$field->getName()] = $field->toArray();
		}

		return $fields;
	}

	public function current(): ?UserField
	{
		return current($this->fields);
	}

	public function next(): void
	{
		next($this->fields);
	}

	public function key(): int
	{
		return key($this->fields);
	}

	public function valid(): bool
	{
		return (key($this->fields) !== null);
	}

	public function rewind(): void
	{
		reset($this->fields);
	}

	public function offsetExists($offset): bool
	{
		return isset($this->fields[$offset]);
	}

	public function offsetGet($offset): ?UserField
	{
		if(is_string($offset))
		{
			return $this->fields[$offset];
		}

		return null;
	}

	public function offsetSet($offset, $value)
	{
		if($value instanceof UserField && $value->getName() === $offset)
		{
			$this->fields[$value->getName()] = $value;
		}
	}

	public function offsetUnset($offset)
	{
		unset($this->fields[$offset]);
	}

	public function count(): int
	{
		return count($this->fields);
	}

	public static function getDefaultVisibility(): array
	{
		return [
			FieldTable::VISIBILITY_VISIBLE => true,
			FieldTable::VISIBILITY_EDITABLE => true,
			FieldTable::VISIBILITY_MANDATORY => false,
			FieldTable::VISIBILITY_KANBAN => false,
			FieldTable::VISIBILITY_CREATE => false,
		];
	}

	protected function getFieldVisibility(string $fieldName, array $visibility, array $defaultVisibility): array
	{
		$fieldVisibility = [];
		foreach(FieldTable::getVisibilityTypes() as $visibilityType)
		{
			if(isset($visibility[$visibilityType][$fieldName]))
			{
				$fieldVisibility[$visibilityType] = true;
			}
			else
			{
				$fieldVisibility[$visibilityType] = $defaultVisibility[$visibilityType];
			}
		}

		return $fieldVisibility;
	}
}