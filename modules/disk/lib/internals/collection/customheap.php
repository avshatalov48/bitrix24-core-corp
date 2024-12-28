<?php

namespace Bitrix\Disk\Internals\Collection;

class CustomHeap extends \SplHeap implements \JsonSerializable
{
	protected \Closure $comparator;

	/**
	 * CustomHeap constructor.
	 * @param \Closure $comparator Comparator.
	 */
	public function __construct(\Closure $comparator)
	{
		$this->comparator = $comparator;
	}

	/**
	 * Compare elements in order to place them correctly in the heap while sifting up.
	 * @link http://php.net/manual/en/splheap.compare.php
	 * @param mixed $element1
	 * @param mixed $element2
	 * @return int Result of the comparison, positive integer if <i>value1</i> is greater than <i>value2</i>, 0 if they are equal, negative integer otherwise.
	 * </p>
	 * <p>
	 * Having multiple elements with the same value in a Heap is not recommended. They will end up in an arbitrary relative position.
	 * @internal param mixed $value1 <p>
	 * The value of the first node being compared.
	 * </p>
	 * @internal param mixed $value2 <p>
	 * The value of the second node being compared.
	 * </p>
	 * @since 5.3.0
	 */
	public function compare($element1, $element2): int
	{
		return call_user_func($this->comparator, $element1, $element2);
	}

	/**
	 * Exports to array heap.
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		$data = [];
		foreach ($this as $item)
		{
			$data[] = $item;
		}

		return $data;
	}

	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
}