<?php

namespace Bitrix\Sign\Item;

use Bitrix\Sign\Contract\ItemCollection;
use Bitrix\SignSafe\Type\FieldType;

// todo: implement base collection class and add inheritance in this place
class FieldCollection implements ItemCollection, \Iterator, \Countable
{
	private array $items;
	/** @var \ArrayIterator<Field> */
	private \ArrayIterator $iterator;

	public function __construct(Field ...$items)
	{
		$this->items = $items;
		$this->iterator = new \ArrayIterator($this->items);
	}

	public function add(Field $item): FieldCollection
	{
		$this->items[] = $item;

		return $this;
	}

	public function clear(): FieldCollection
	{
		$this->items = [];

		return $this;
	}

	public function toArray(): array
	{
		return $this->items;
	}

	public function isFilled(): bool
	{
		foreach ($this->items as $item)
		{
			if (!$item->isFilled())
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * @return string[]
	 */
	public function listNames(): array
	{
		return array_map(fn (Field $item) => $item->name, $this->items);
	}

	public function getFirstFieldByName(string $name): ?Field
	{
		foreach ($this->items as $item)
		{
			if ($item->name === $name)
			{
				return $item;
			}
		}

		return null;
	}

	public function getById(int $id): ?Field
	{
		foreach ($this->items as $item)
		{
			if ($item->id === $id)
			{
				return $item;
			}
		}

		return null;
	}

	public function current(): ?Field
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

	public function count(): int
	{
		return count($this->items);
	}

	public function isEmpty(): bool
	{
		return empty($this->items);
	}

	public function getFirst(): ?Field
	{
		return $this->items[0] ?? null;
	}

	final public function findFirst(\Closure $rule): ?Field
	{
		foreach ($this as $item)
		{
			if ($rule($item))
			{
				return $item;
			}
		}

		return null;
	}

	final public function filter(\Closure $rule): FieldCollection
	{
		$result = new FieldCollection();
		foreach ($this as $item)
		{
			if ($rule($item))
			{
				$result->add($item);
			}
		}

		return $result;
	}

	final public function filterWithValuesByMemberId(int $memberId): FieldCollection
	{
		$collection = new FieldCollection();
		foreach ($this->items as $item)
		{
			$newItem = clone $item;

			$newItem->values = $item->values?->filter(
				static fn(Field\Value $value) => $value->memberId === $memberId,
			);

			if (!empty($newItem->values))
			{
				$collection->add($newItem);
			}
		}

		return $collection;
	}

	final public function filterByParty(int $party): FieldCollection
	{
		return $this->filter(static fn(Field $field) => $field->party === $party);
	}

	public function existWithName(string $name): bool
	{
		foreach ($this->toArray() as $item)
		{
			if ($item->name === $name)
			{
				return true;
			}
		}

		return false;
	}

	final public function mergeFieldsWithNoneIncludedName(self $fields): static
	{
		foreach ($fields as $field)
		{
			if (!$this->existWithName($field->name))
			{
				$this->add($field);
			}
		}

		return $this;
	}

	/**
	 * @return array<string, Field>
	 */
	public function getNameMap(): array
	{
		$map = [];

		foreach ($this->items as $field)
		{
			$map[$field->name] = $field;
		}

		return $map;
	}
}