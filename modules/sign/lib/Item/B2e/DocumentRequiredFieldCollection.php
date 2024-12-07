<?php

namespace Bitrix\Sign\Item\B2e;

use Bitrix\Sign\Contract\Item;
use Bitrix\Sign\Contract\ItemCollection;

/**
 * @implements \IteratorAggregate<int, DocumentRequiredField>
 */
class DocumentRequiredFieldCollection implements Item, ItemCollection, \IteratorAggregate, \Countable, \JsonSerializable
{
	/** @var \ArrayIterator<DocumentRequiredField> */
	private \ArrayIterator $iterator;

	private array $items;

	public function __construct(DocumentRequiredField ...$items)
	{
		$this->items = $items;
		$this->iterator = new \ArrayIterator($items);
	}

	public function add(DocumentRequiredField $item): self
	{
		$this->items[] = $item;
		$this->iterator->append($item);

		return $this;
	}

	/**
	 * @return list<DocumentRequiredField>
	 */
	public function all(): array
	{
		return $this->iterator->getArrayCopy();
	}


	public function toArray(): array
	{
		return array_map(fn(DocumentRequiredField $type) => $type->toArray(), $this->all());
	}

	public function getIterator(): \ArrayIterator
	{
		return $this->iterator;
	}

	public function count(): int
	{
		return $this->getIterator()->count();
	}

	public function current(): ?DocumentRequiredField
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

	public function convertToRequiredFieldCollection(): RequiredFieldsCollection
	{
		$items = array_map(
			fn(DocumentRequiredField $item): RequiredField => new RequiredField($item->type, $item->role),
			$this->all(),
		);
		return new RequiredFieldsCollection(...$items);
	}

}