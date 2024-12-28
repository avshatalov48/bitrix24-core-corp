<?php

namespace Bitrix\Sign\Item;

use Bitrix\Sign\Contract\ItemCollection;
use Bitrix\Sign\Helper\IterationHelper;

class DocumentCollection implements ItemCollection, \Iterator, \Countable
{
	private array $items;
	/** @var \ArrayIterator<Document> */
	private \ArrayIterator $iterator;

	public function __construct(Document ...$items)
	{
		$this->items = $items;
		$this->iterator = new \ArrayIterator($this->items);
	}

	public function add(Document $item): DocumentCollection
	{
		$this->items[] = $item;

		return $this;
	}

	public function clear(): DocumentCollection
	{
		$this->items = [];

		return $this;
	}

	public function getById(int $id): ?Document
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

	public function getByEntityId(int $entityId): ?Document
	{
		foreach ($this->items as $item)
		{
			if ((int)$item->entityId === $entityId)
			{
				return $item;
			}
		}

		return null;
	}

	public function toArray(): array
	{
		return $this->items;
	}

	public function current(): ?Document
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

	public function all(\Closure $rule): bool
	{
		return IterationHelper::all($this->items, $rule);
	}

	public function any(\Closure $rule): bool
	{
		return IterationHelper::any($this->items, $rule);
	}

	public function filter(\Closure $rule): DocumentCollection
	{
		$result = new static();
		foreach ($this as $item)
		{
			if ($rule($item))
			{
				$result->add($item);
			}
		}

		return $result;
	}

	/**
	 * Get array with keys - values of id field
	 *
	 * @return array<int, Document>
	 */
	public function getArrayByIds(): array
	{
		$documents = [];
		foreach ($this->items as $documentItem)
		{
			$documents[$documentItem->id] = $documentItem;
		}
		return $documents;
	}

	public function findByTemplateId(?int $id): ?Document
	{
		foreach ($this->items as $item)
		{
			if ($item->templateId === $id)
			{
				return $item;
			}
		}

		return null;
	}

	public function sortByTemplateIdsDesc(): static
	{
		$items = $this->items;
		usort($items, static function (Document $a, Document $b) {
			return $b->templateId <=> $a->templateId;
		});

		return new static(...$items);
	}

	/**
	 * @return list<int>
	 */
	public function listIdsWithoutNull(): array
	{
		$ids = [];
		foreach ($this->items as $item)
		{
			if ($item->id !== null)
			{
				$ids[] = $item->id;
			}
		}

		return $ids;
	}

	public function getFirst(): ?Document
	{
		return $this->items[0] ?? null;
	}
}
