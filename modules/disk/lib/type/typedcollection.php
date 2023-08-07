<?php

namespace Bitrix\Disk\Type;

use Bitrix\Disk\Internals\Model;

abstract class TypedCollection implements \IteratorAggregate
{
	/** @var Model[] */
	private array $items = [];

	protected function __construct(Model ...$items)
	{
		$this->items = $items;
	}

	abstract protected static function getItemClass(): string;

	public static function createByIds(int ...$id): self
	{
		$itemClass = static::getItemClass();
		$models = $itemClass::getModelList([
			'filter' => [
				'ID' => $id,
			],
		]);

		return new static(...$models);
	}

	public function getIterator(): \ArrayIterator
	{
		return new \ArrayIterator($this->items);
	}

	public function toArray(): array
	{
		return $this->items;
	}

	public function getIds(): array
	{
		$ids = [];
		foreach ($this->items as $item)
		{
			$ids[] = $item->getId();
		}

		return $ids;
	}
}