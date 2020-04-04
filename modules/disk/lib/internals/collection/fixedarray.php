<?php
namespace Bitrix\Disk\Internals\Collection;

use Bitrix\Main\InvalidOperationException;

final class FixedArray implements \Iterator, \ArrayAccess, \Countable
{
	/** @var \SplFixedArray */
	protected $fixedArray;
	/** @var int */
	private $lastIndexToPush = 0;

	public function __construct($size)
	{
		$this->fixedArray = new \SplFixedArray($size);
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
		return $this->fixedArray->__wakeup();
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
	 * Return the current element
	 * @link http://php.net/manual/en/iterator.current.php
	 * @return mixed Can return any type.
	 * @since 5.0.0
	 */
	public function current()
	{
		return $this->fixedArray->current();
	}

	/**
	 * Move forward to next element
	 * @link http://php.net/manual/en/iterator.next.php
	 * @return void Any returned value is ignored.
	 * @since 5.0.0
	 */
	public function next()
	{
		return $this->fixedArray->next();
	}

	/**
	 * Return the key of the current element
	 * @link http://php.net/manual/en/iterator.key.php
	 * @return mixed scalar on success, or null on failure.
	 * @since 5.0.0
	 */
	public function key()
	{
		return $this->fixedArray->key();
	}

	/**
	 * Checks if current position is valid
	 * @link http://php.net/manual/en/iterator.valid.php
	 * @return boolean The return value will be casted to boolean and then evaluated.
	 * Returns true on success or false on failure.
	 * @since 5.0.0
	 */
	public function valid()
	{
		return $this->fixedArray->valid();
	}

	/**
	 * Rewind the Iterator to the first element
	 * @link http://php.net/manual/en/iterator.rewind.php
	 * @return void Any returned value is ignored.
	 * @since 5.0.0
	 */
	public function rewind()
	{
		$this->fixedArray->rewind();
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
	public function offsetExists($offset)
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
	public function offsetSet($offset, $value)
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
	public function offsetUnset($offset)
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
	public function count()
	{
		return $this->fixedArray->getSize();
	}
}