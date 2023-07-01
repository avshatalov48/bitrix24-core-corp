<?php

namespace Bitrix\Disk\Type;

use Bitrix\Disk\BaseObject;

final class ObjectCollection implements \IteratorAggregate
{
	private $items = [];

	private function __construct(BaseObject ...$baseObjects)
	{
		$this->items = $baseObjects;
	}

	public static function createByIds(...$id)
	{
		array_walk($id, 'intval');

		$models = BaseObject::getModelList([
			'filter' => [
				'ID' => $id,
			],
		]);

		return new static(...$models);
	}

	/**
	 * Retrieve an external iterator
	 * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
	 * @return \Traversable An instance of an object implementing <b>Iterator</b> or
	 * <b>Traversable</b>
	 * @since 5.0.0
	 */
	public function getIterator(): \ArrayIterator
	{
		return new \ArrayIterator($this->items);
	}

	/**
	 * @return array|BaseObject[]
	 */
	public function toArray()
	{
		return $this->items;
	}

	public function getIds()
	{
		$ids = [];
		foreach ($this->items as $item)
		{
			$ids[] = $item->getId();
		}

		return $ids;
	}
}