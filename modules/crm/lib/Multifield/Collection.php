<?php

namespace Bitrix\Crm\Multifield;

use Bitrix\Main\Type\Contract\Arrayable;

final class Collection implements Arrayable, \Iterator, \Countable
{
	/** @var Value[] */
	private $values = [];

	/**
	 * Add a new value to the collection.
	 * If an equal value is already in this collection, duplicating value won't be added.
	 *
	 * @param Value $value
	 * @return $this
	 */
	public function add(Value $value): self
	{
		foreach ($this->values as $existingValue)
		{
			if ($existingValue->isEqualTo($value))
			{
				return $this;
			}
		}

		$this->values[] = $value;

		return $this;
	}

	public function remove(Value $value): self
	{
		foreach ($this->values as $index => $existingValue)
		{
			if ($value->isEqualTo($existingValue))
			{
				unset($this->values[$index]);
			}
		}

		return $this;
	}

	public function removeById(int $id): self
	{
		foreach ($this->values as $index => $value)
		{
			if ($value->getId() === $id)
			{
				unset($this->values[$index]);

				return $this;
			}
		}

		return $this;
	}

	public function getById(int $id): ?Value
	{
		foreach ($this->values as $value)
		{
			if ($value->getId() === $id)
			{
				return $value;
			}
		}

		return null;
	}

	public function has(Value $value): bool
	{
		foreach ($this->values as $existingValue)
		{
			if ($existingValue->isEqualTo($value))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Get a flat array with all values contained in this collection
	 *
	 * @return Value[]
	 */
	public function getAll(): array
	{
		return array_values($this->values);
	}

	/**
	 * Returns **new** collection with values that belong to type with the provided $typeId.
	 *
	 * This collection is **not mutated**. References to values are **not shared**.
	 *
	 * @param string $typeId
	 * @return self
	 */
	public function filterByType(string $typeId): self
	{
		$result = new self();

		foreach ($this as $value)
		{
			if ($value->getTypeId() === $typeId)
			{
				$result->add(clone $value);
			}
		}

		return $result;
	}

	public function isEqualTo(self $anotherCollection): bool
	{
		$thisValues = $this->getAll();
		$anotherValues = $anotherCollection->getAll();

		if (count($thisValues) !== count($anotherValues))
		{
			return false;
		}

		$thisHashes = [];
		foreach ($thisValues as $thisValue)
		{
			$thisHashes[] = $thisValue->getHash();
		}
		sort($thisHashes);

		$anotherHashes = [];
		foreach ($anotherValues as $anotherValue)
		{
			$anotherHashes[] = $anotherValue->getHash();
		}
		sort($anotherHashes);

		return ($thisHashes === $anotherHashes);
	}

	public function isEmpty(): bool
	{
		return count($this->values) <= 0;
	}

	public function toArray(): array
	{
		return Assembler::arrayByCollection($this);
	}

	public function current(): Value|false
	{
		return current($this->values);
	}

	public function next(): void
	{
		next($this->values);
	}

	public function key(): ?int
	{
		return key($this->values);
	}

	public function valid(): bool
	{
		return $this->key() !== null;
	}

	public function rewind(): void
	{
		reset($this->values);
	}

	public function count(): int
	{
		return count($this->values);
	}

	public function __clone()
	{
		$this->values = \Bitrix\Main\Type\Collection::clone($this->values);
	}
}
