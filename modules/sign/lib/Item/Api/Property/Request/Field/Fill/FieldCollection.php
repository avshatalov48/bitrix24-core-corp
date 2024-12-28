<?php

namespace Bitrix\Sign\Item\Api\Property\Request\Field\Fill;

use Bitrix\Sign\Contract;

class FieldCollection implements Contract\ItemCollection, Contract\Item, \Countable
{
	/** @var Field[] */
	private array $items;

	public function __construct(Field ...$items)
	{
		$this->items = $items;
	}

	public function addItem(Field $item): self
	{
		$this->items[] = $item;
		return $this;
	}

	public function toArray(): array
	{
		return $this->items;
	}

	public function count(): int
	{
		return count($this->toArray());
	}

	final public function isEmpty(): bool
	{
		return empty($this->items);
	}

	/**
	 * @return array<string>
	 */
	public function getNames(): array
	{
		return array_map(static fn(Field $field): string => $field->name, $this->toArray());
	}
}