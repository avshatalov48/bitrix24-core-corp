<?php

namespace Bitrix\Sign\Item;

use Bitrix\Sign\Contract\ItemCollection;

final class CompanyCollection implements ItemCollection, \IteratorAggregate, \Countable
{
	/** @var \ArrayIterator<Company> */
	private \ArrayIterator $iterator;

	public function __construct(Company ...$items)
	{
		$this->iterator = new \ArrayIterator($items);
	}

	public function add(Company $item): CompanyCollection
	{
		$this->iterator->append($item);

		return $this;
	}

	public function getById(int $id): ?Company
	{
		/** @var Company $company */
		foreach ($this->iterator as $item)
		{
			if ($item->id === $id)
			{
				return $item;
			}
		}

		return null;
	}

	/**
	 * @return array|int[]
	 */
	public function getIds(): array
	{
		$ids = [];
		/** @var Company $company */
		foreach ($this->iterator as $company)
		{
			$ids[] = $company->id;
		}

		return $ids;
	}

	public function toArray(): array
	{
		return $this->iterator->getArrayCopy();
	}

	public function getIterator(): \ArrayIterator
	{
		return $this->iterator;
	}

	public function count(): int
	{
		return $this->getIterator()->count();
	}

	public function sortProviders(): self
	{
		/** @var Company $company */
		foreach ($this->iterator as $company)
		{
			usort(
				$company->providers,
				fn(CompanyProvider $a, CompanyProvider $b) => $b->timestamp <=> $a->timestamp,
			);
		}

		return $this;
	}
}
