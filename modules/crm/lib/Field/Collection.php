<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;

class Collection implements \Iterator, \ArrayAccess, \Countable
{
	/** @var Field[] */
	protected $fields = [];

	/**
	 * @param Field[] $fields
	 */
	public function __construct(array $fields)
	{
		foreach ($fields as $field)
		{
			$this->fields[$field->getName()] = $field;
		}
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

	public function hasField(string $fieldName): bool
	{
		return ($this->getField($fieldName) !== null);
	}

	public function getField(string $fieldName): ?Field
	{
		return $this[$fieldName];
	}

	public function getFieldNameList(): array
	{
		$names = [];
		foreach ($this->fields as $field)
		{
			$names[] = $field->getName();
		}

		return $names;
	}

	/**
	 * Returns a new collection, that contains all fields of this collection with specific type
	 */
	final public function getFieldsByType(string $fieldType): self
	{
		$fields = [];
		foreach ($this->fields as $field)
		{
			if ($field->getType() === $fieldType)
			{
				$fields[] = $field;
			}
		}

		return new self($fields);
	}

	public function current(): ?Field
	{
		return current($this->fields);
	}

	public function next(): void
	{
		next($this->fields);
	}

	public function key(): string
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

	public function offsetExists(mixed $offset): bool
	{
		return isset($this->fields[$offset]);
	}

	public function offsetGet(mixed $offset): ?Field
	{
		if(isset($this->fields[$offset]) && is_string($offset))
		{
			return $this->fields[$offset];
		}

		return null;
	}

	public function offsetSet(mixed $offset, mixed $value): void
	{
		if($value instanceof Field && $value->getName() === $offset)
		{
			$this->fields[$value->getName()] = $value;
		}
	}

	public function offsetUnset(mixed $offset): void
	{
		unset($this->fields[$offset]);
	}

	public function count(): int
	{
		return count($this->fields);
	}

	/**
	 * Return array of field names that has attribute Hidden
	 *
	 * @return string[]
	 */
	public function getHiddenFieldNames(): array
	{
		$fieldNames = [];

		foreach ($this->fields as $field)
		{
			if ($field->isHidden())
			{
				$fieldNames[] = $field->getName();
			}
		}

		return $fieldNames;
	}

	/**
	 * Remove keys from $data that among hidden fields of this collection.
	 *
	 * @param array $data
	 * @return array
	 */
	public function removeHiddenValues(array $data): array
	{
		$hiddenFieldNames = $this->getHiddenFieldNames();

		return static::removeHiddenValuesByFieldNames($data, $hiddenFieldNames);
	}

	/**
	 * Remove keys from $data that present in $hiddenFieldNames.
	 *
	 * @param array $data
	 * @param string[] $hiddenFieldNames
	 * @return array
	 */
	public static function removeHiddenValuesByFieldNames(array $data, array $hiddenFieldNames): array
	{
		$hiddenFieldNames = array_combine($hiddenFieldNames, $hiddenFieldNames);

		return array_diff_key($data, $hiddenFieldNames);
	}
}
