<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;

class Collection implements \Iterator, \ArrayAccess, \Countable
{
	/** @var Field[] */
	protected $fields;

	/**
	 * @param Field[] $fields
	 */
	public function __construct(array $fields)
	{
		$this->fields = $fields;
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

	public function current(): ?Field
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

	public function offsetGet($offset): ?Field
	{
		if(is_string($offset))
		{
			return $this->fields[$offset];
		}

		return null;
	}

	public function offsetSet($offset, $value)
	{
		if($value instanceof Field && $value->getName() === $offset)
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
}