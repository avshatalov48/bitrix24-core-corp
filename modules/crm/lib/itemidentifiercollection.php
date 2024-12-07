<?php

namespace Bitrix\Crm;

final class ItemIdentifierCollection implements \Iterator, \ArrayAccess, \Countable
{
	/**
	 * @var ItemIdentifier[] $itemIdentifiers
	 */
	protected array $itemIdentifiers = [];

	/**
	 * @param ItemIdentifier[] $itemIdentifiers
	 */
	public function __construct(array $itemIdentifiers = [])
	{
		foreach ($itemIdentifiers as $itemIdentifier)
		{
			$this->append($itemIdentifier);
		}
	}

	public function append(ItemIdentifier $itemIdentifier): void
	{
		$this->itemIdentifiers[$this->getCode($itemIdentifier)] = $itemIdentifier;
	}

	private function getCode(ItemIdentifier $itemIdentifier): string
	{
		$values = [$itemIdentifier->getEntityTypeId(), $itemIdentifier->getEntityId()];
		if ($itemIdentifier->getCategoryId() !== null)
		{
			$values[] = $itemIdentifier->getCategoryId();
		}

		return implode('-', $values);
	}

	public function toArray(): array
	{
		$itemIdentifiers = [];
		foreach ($this->itemIdentifiers as $itemIdentifier)
		{
			$itemIdentifiers[] = $itemIdentifier->toArray();
		}

		return $itemIdentifiers;
	}

	public function hasItemIdentifier(string $code): bool
	{
		return ($this->getItemIdentifier($code) !== null);
	}

	public function getItemIdentifier(string $code): ?ItemIdentifier
	{
		return $this[$code] ?? null;
	}

	public function current(): ?ItemIdentifier
	{
		return current($this->itemIdentifiers);
	}

	public function next(): void
	{
		next($this->itemIdentifiers);
	}

	public function key(): string
	{
		return key($this->itemIdentifiers);
	}

	public function valid(): bool
	{
		return (key($this->itemIdentifiers) !== null);
	}

	public function rewind(): void
	{
		reset($this->itemIdentifiers);
	}

	public function offsetExists($offset): bool
	{
		return isset($this->itemIdentifiers[$offset]);
	}

	public function offsetGet($offset): ?ItemIdentifier
	{
		if(isset($this->itemIdentifiers[$offset]) && is_string($offset))
		{
			return $this->itemIdentifiers[$offset];
		}

		return null;
	}

	public function offsetSet($offset, $value): void
	{
		if ($value instanceof ItemIdentifier && $this->getCode($value) === $offset)
		{
			$this->itemIdentifiers[$this->getCode($value)] = $value;
		}
	}

	public function offsetUnset($offset): void
	{
		unset($this->itemIdentifiers[$offset]);
	}

	public function count(): int
	{
		return count($this->itemIdentifiers);
	}
}