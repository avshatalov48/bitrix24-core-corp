<?php

namespace Bitrix\Sign\Item\B2e;

use Bitrix\Sign\Contract\Item;
use Bitrix\Sign\Contract\ItemCollection;
use Bitrix\Sign\Type\FieldType;
use Bitrix\Sign\Type\Member\Role;

/**
 * @implements \IteratorAggregate<int, RequiredField>
 */
class RequiredFieldsCollection implements Item, ItemCollection, \IteratorAggregate, \Countable, \JsonSerializable
{
	/** @var \ArrayIterator<RequiredField> */
	private \ArrayIterator $iterator;

	private array $items;

	public function __construct(RequiredField ...$items)
	{
		$this->items = $items;
		$this->iterator = new \ArrayIterator($items);
	}

	public function add(RequiredField $item): self
	{
		$this->items[] = $item;
		$this->iterator->append($item);

		return $this;
	}

	/**
	 * @return list<RequiredField>
	 */
	public function all(): array
	{
		return $this->iterator->getArrayCopy();
	}


	public function toArray(): array
	{
		return array_map(fn(RequiredField $type) => $type->toArray(), $this->all());
	}

	public function getIterator(): \ArrayIterator
	{
		return $this->iterator;
	}

	public function count(): int
	{
		return $this->getIterator()->count();
	}

	public function current(): ?RequiredField
	{
		return $this->iterator->current();
	}

	public function next(): void
	{
		$this->iterator->next();
	}

	public function key(): int
	{
		return $this->iterator->key();
	}

	public function valid(): bool
	{
		return $this->iterator->valid();
	}

	public function rewind(): void
	{
		$this->iterator = new \ArrayIterator($this->items);
	}

	public function jsonSerialize(): array
	{
		return $this->toArray();
	}

	public function isEmpty(): bool
	{
		return !$this->count();
	}

	public static function createFromJsonArray(array $decodedFields): static
	{
		$fields = new static();
		foreach ($decodedFields as $field)
		{
			if (isset($field['type'])
				&& in_array((string)$field['type'], FieldType::getAll(), true)
				&& isset($field['role'])
				&& Role::isValid((string)$field['role'])
			)
			{
				$fields->add(new RequiredField((string)$field['type'], (string)$field['role']));
			}
		}

		return $fields;
	}
}