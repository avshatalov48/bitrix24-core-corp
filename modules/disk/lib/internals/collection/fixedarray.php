<?php
namespace Bitrix\Disk\Internals\Collection;

use Bitrix\Main\InvalidOperationException;

final class FixedArray implements \IteratorAggregate, \ArrayAccess, \Countable
{
	/** @var \SplFixedArray */
	protected $fixedArray;
	/** @var int */
	private $lastIndexToPush = 0;

	public function __construct($size)
	{
		$this->fixedArray = new \SplFixedArray($size);
	}

	public function getIterator(): \Iterator
	{
		if ($this->getSplFixedArray() instanceof \IteratorAggregate)
		{
			return $this->getSplFixedArray()->getIterator();
		}

		return new Compatibility\FixedArrayIterator($this->getSplFixedArray());
	}

	/**
	 * Creates FixedArray from array. Does not preserve keys.
	 *
	 * @param $items
	 * @return static
	 */
	public static function fromArray($items)
	{
		$fixedArray = new static(count($items));
		foreach ($items as $item)
		{
			$fixedArray->push($item);
		}

		return $fixedArray;
	}

	/**
	 * Returns size of fixed array.
	 *
	 * @return int
	 */
	public function getSize()
	{
		return $this->fixedArray->getSize();
	}

	/**
	 * Sets size fixed array.
	 *
	 * @param $size
	 * @return int
	 */
	public function setSize($size)
	{
		return $this->fixedArray->setSize($size);
	}

	/**
	 * Exports to array.
	 *
	 * @return array
	 */
	public function toArray()
	{
		return $this->fixedArray->toArray();
	}

	public function __wakeup()
	{
		$this->fixedArray->__wakeup();
	}

	/**
	 * @return \SplFixedArray
	 */
	public function getSplFixedArray()
	{
		return $this->fixedArray;
	}

	/**
	 * Returns iterator to reverse fixed array.
	 *
	 * @return ReverseIterator
	 */
	public function reverse()
	{
		return new ReverseIterator($this);
	}

	/**
	 * Creates new element in array like operator "[]" in array.
	 *
	 * @param mixed $data Mixed data.
	 * @throws InvalidOperationException
	 */
	public function push($data)
	{
		if(isset($this[$this->lastIndexToPush]))
		{
			throw new InvalidOperationException('Could not push data, the element already exists.');
		}

		$this->fixedArray[$this->lastIndexToPush++] = $data;
	}

	/**
	 * Returns count of elements which were pushed by @see FixedArray::push();
	 *
	 * It may be useful, because count(), getSize() returns value which was initialized.
	 *
	 * @return int
	 */
	public function getCountOfPushedElements()
	{
		return $this->lastIndexToPush;
	}

	/**
	 * Whether a offset exists
	 * @link http://php.net/manual/en/arrayaccess.offsetexists.php
	 *
	 * @param mixed $offset <p>
	 * An offset to check for.
	 * </p>
	 *
	 * @return boolean true on success or false on failure.
	 * </p>
	 * <p>
	 * The return value will be casted to boolean if non-boolean was returned.
	 * @since 5.0.0
	 */
	public function offsetExists($offset): bool
	{
		return $this->fixedArray->offsetExists($offset);
	}

	/**
	 * Offset to retrieve
	 * @link http://php.net/manual/en/arrayaccess.offsetget.php
	 *
	 * @param mixed $offset <p>
	 * The offset to retrieve.
	 * </p>
	 *
	 * @return mixed Can return all value types.
	 * @since 5.0.0
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet($offset)
	{
		return $this->fixedArray->offsetGet($offset);
	}

	/**
	 * Offset to set
	 * @link http://php.net/manual/en/arrayaccess.offsetset.php
	 *
	 * @param mixed $offset <p>
	 * The offset to assign the value to.
	 * </p>
	 * @param mixed $value <p>
	 * The value to set.
	 * </p>
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function offsetSet($offset, $value): void
	{
		if (is_null($offset))
		{
			$this->push($value);
		}
		else
		{
			$this->fixedArray[$offset] = $value;
		}
	}

	/**
	 * Offset to unset
	 * @link http://php.net/manual/en/arrayaccess.offsetunset.php
	 *
	 * @param mixed $offset <p>
	 * The offset to unset.
	 * </p>
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function offsetUnset($offset): void
	{
		unset($this->fixedArray[$offset]);
	}

	/**
	 * Count elements of an object
	 * @link http://php.net/manual/en/countable.count.php
	 * @return int The custom count as an integer.
	 * </p>
	 * <p>
	 * The return value is cast to an integer.
	 * @since 5.1.0
	 */
	public function count(): int
	{
		return $this->fixedArray->getSize();
	}
}