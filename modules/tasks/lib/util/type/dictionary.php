<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * This class is slightly different from \Bitrix\Main\Type\Dictionary
 */

namespace Bitrix\Tasks\Util\Type;

abstract class Dictionary implements \IteratorAggregate, \ArrayAccess, \Countable
{
	protected $values = array();

	/**
	 * Creates object.
	 *
	 * @param array $values
	 */
	public function __construct(array $values = null)
	{
		if($values !== null)
		{
			$this->values = $values;
		}
	}

	public function getIterator()
	{
		return new \ArrayIterator($this->values);
	}

	/**
	 * Returns item by it`s value (key). Keys MAY NOT be numeric, so it is NOT an order-correct query
	 *
	 * @param $name
	 * @return null
	 */
	public function get($name)
	{
		// this condition a bit faster
		// it is possible to omit array_key_exists here, but for uniformity...
		if (isset($this->values[$name]) || array_key_exists($name, $this->values))
		{
			return $this->values[$name];
		}

		return null;
	}

	public function set(array $values)
	{
		$this->values = $values;
		$this->onChange();
	}

	public function clear()
	{
		$this->values = array();
		$this->onChange();
	}

	/**
	 * Whether a offset exists
	 */
	public function offsetExists($offset)
	{
		return array_key_exists($offset, $this->values);
	}

	/**
	 * Returns instance field. If none was loaded before, upload it.
	 *
	 * @param string $offset
	 * @return string | null
	 */
	public function offsetGet($offset)
	{
		if (isset($this->values[$offset]) || array_key_exists($offset, $this->values))
		{
			return $this->values[$offset];
		}

		return null;
	}

	/**
	 * Offset to set
	 */
	public function offsetSet($offset, $value)
	{
		if($offset === null)
		{
			$this->values[] = $value;
		}
		else
		{
			$this->values[$offset] = $value;
		}
		$this->onChange();
	}

	/**
	 * Offset to unset
	 */
	public function offsetUnset($offset)
	{
		unset($this->values[$offset]);
		$this->onChange();
	}

	/**
	 * Count elements of an object
	 */
	public function count()
	{
		return count($this->values);
	}

	/**
	 * Returns the values as an array.
	 *
	 * @return array
	 */
	public function toArray()
	{
		return $this->values;
	}

	/**
	 * Returns true if the dictionary is empty.
	 * @return bool
	 */
	public function isEmpty()
	{
		return empty($this->values);
	}

	public function containsKey($key)
	{
		return array_key_exists($key, $this->values);
	}

	public function getKeys()
	{
		return array_keys($this->values);
	}

	protected function onChange()
	{
	}

	public static function getClass()
	{
		return get_called_class();
	}

	public static function isA($object)
	{
		return is_a($object, static::getClass());
	}
}