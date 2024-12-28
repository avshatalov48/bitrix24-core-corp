<?php

namespace Bitrix\BIConnector;

use Traversable;

class Collection
	implements \ArrayAccess, \Countable, \IteratorAggregate
{
	protected array $collection = [];

	public function add(mixed $value): void
	{
		$this->offsetSet(null, $value);
	}

	/**
	 * @return Traversable
	 */
	public function getIterator(): Traversable
	{
		return new \ArrayIterator($this->collection);
	}

	/**
	 * Whether an offset exists
	 */
	public function offsetExists(mixed $offset): bool
	{
		return isset($this->collection[$offset]) || array_key_exists($offset, $this->collection);
	}

	/**
	 * Offset to retrieve
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet(mixed $offset)
	{
		if (isset($this->collection[$offset]) || array_key_exists($offset, $this->collection))
		{
			return $this->collection[$offset];
		}

		return null;
	}

	/**
	 * Offset to set
	 */
	public function offsetSet(mixed $offset, mixed $value): void
	{
		if($offset === null)
		{
			$this->collection[] = $value;
		}
		else
		{
			$this->collection[$offset] = $value;
		}
	}

	/**
	 * Offset to unset
	 */
	public function offsetUnset(mixed $offset): void
	{
		unset($this->collection[$offset]);
	}

	/**
	 * Count elements of an object
	 */
	public function count(): int
	{
		return count($this->collection);
	}

	/**
	 * Return the current element
	 */
	public function current()
	{
		return current($this->collection);
	}

	/**
	 * Move forward to next element
	 */
	public function next(): mixed
	{
		return next($this->collection);
	}

	/**
	 * Return the key of the current element
	 */
	public function key(): int|string|null
	{
		return key($this->collection);
	}

	/**
	 * Checks if current position is valid
	 */
	public function valid(): bool
	{
		$key = $this->key();
		return $key !== null;
	}

	/**
	 * Rewind the Iterator to the first element
	 */
	public function rewind(): mixed
	{
		return reset($this->collection);
	}

	/**
	 * Checks if collection is empty.
	 *
	 * @return bool
	 */
	public function isEmpty(): bool
	{
		return empty($this->collection);
	}
}
