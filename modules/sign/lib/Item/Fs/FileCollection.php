<?php
namespace Bitrix\Sign\Item\Fs;

use Bitrix\Sign\Contract;

class FileCollection implements Contract\ItemCollection, \Iterator, \Countable
{
	/** @var File[] */
	private array $items;
	/** @var \ArrayIterator<File> */
	private \ArrayIterator $iterator;

	public function __construct(File ...$items)
	{
		$this->items = $items;
		$this->iterator = new \ArrayIterator($this->items);
	}

	public function addItem(File $item): self
	{
		$this->items[] = $item;
		return $this;
	}

	public function first(): ?File
	{
		return $this->items[0] ?? null;
	}

	public function shift(): ?File
	{
		return $this->items[0] ? array_shift($this->items) : null;
	}

	public function toArray(): array
	{
		return $this->items;
	}

	public function count(): int
	{
		return count($this->items);
	}

	public function current(): ?File
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

	/**
	 * @return list<int>
	 */
	public function getIds(): array
	{
		$ids = [];
		foreach ($this->items as $file)
		{
			if ($file->id !== null)
			{
				$ids[] = $file->id;
			}
		}

		return $ids;
	}
}