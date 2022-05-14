<?php

namespace Bitrix\Crm\Multifield;

use Bitrix\Main\Type\Contract\Arrayable;

final class Collection implements Arrayable, \Iterator
{
	/** @var Value[] */
	private $values = [];

	public function add(Value $value): self
	{
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

				return $this;
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

	/**
	 * Get a flat array with all values contained in this collection
	 *
	 * @return Value[]
	 */
	public function getAll(): array
	{
		return array_values($this->values);
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

	public function toArray(): array
	{
		return Assembler::arrayByCollection($this);
	}

	/**
	 * @return Value|false
	 */
	public function current()
	{
		return current($this->values);
	}

	public function next(): void
	{
		next($this->values);
	}

	public function key()
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

	public function __clone()
	{
		$this->values = \Bitrix\Main\Type\Collection::clone($this->values);
	}
}
