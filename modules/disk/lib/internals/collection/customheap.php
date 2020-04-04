<?php

namespace Bitrix\Disk\Internals\Collection;

use Bitrix\Main\ArgumentTypeException;

class CustomHeap extends \SplHeap
{
	/** @var callable */
	protected $comparator;

	/**
	 * CustomHeap constructor.
	 * @param callable $comparator Comparator.
	 * @throws ArgumentTypeException
	 */
	public function __construct($comparator)
	{
		if(!is_callable($comparator))
		{
			throw new ArgumentTypeException('callable', 'Callable');
		}

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
	public function compare($element1, $element2)
	{
		return call_user_func($this->comparator, $element1, $element2);
	}

	/**
	 * Exports to array heap.
	 *
	 * @return array
	 */
	public function toArray()
	{
		$data = array();

		foreach($this as $item)
		{
			$data[] = $item;
		}
		unset($item);

		return $data;
	}
}