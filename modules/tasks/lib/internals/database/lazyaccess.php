<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @internal
 */

namespace Bitrix\Tasks\Internals\DataBase;

use Bitrix\Tasks\Item\Field\Map;

abstract class LazyAccess extends \Bitrix\Tasks\Util\Type\Dictionary
{
	protected static $mapCache = array();

	protected static function generateMap(array $parameters = array())
	{
		return new Map();
	}

	/**
	 * @return \Bitrix\Tasks\Item\Field\Map
	 */
	public function getMap()
	{
		$cName = static::getClass();
		if(!array_key_exists($cName, static::$mapCache))
		{
			static::$mapCache[$cName] = static::generateMap();
		}

		return static::$mapCache[$cName];
	}

	protected function resetMapCache()
	{
		unset(static::$mapCache[static::getClass()]);
	}

	protected function fetchFieldValue($k)
	{
		return null;
	}

	public function getCachedFields()
	{
		return array_keys($this->values);
	}

	/**
	 * Returns instance field. If none was loaded before, upload it.
	 *
	 * @param string $offset
	 * @return string | null
	 */
	public function offsetGet($offset)
	{
		$map = $this->getMap();

		if(!isset($map[$offset]))
		{
			return null;
		}

		if($this->values[$offset] === null)
		{
			$value = $this->fetchFieldValue($offset);

			if(!$map[$offset]->isInternallyCacheAble())
			{
				$this->values[$offset] = $value;
			}
		}
		else
		{
			$value = $this->values[$offset];
		}

		return $value;
	}

	public function getIterator()
	{
		$iterator = array();
		foreach($this->getMap() as $k => $v)
		{
			$iterator[$k] = $this[$k]; // lazy load will work here
		}

		return new \ArrayIterator($iterator); // todo: object pool here
	}

	public function __call($name, array $arguments)
	{
		// todo: implement functional way of setting and getting fields: i.e. setFieldDescription() and getFieldDescription()
		if(strpos('getField', $name) == 0)
		{
			$name = substr($name, 8); // strip getField
			$name = $this->getMap()->decodeCamelFieldName($name);
			if($name)
			{
				return $this[$name];
			}
		}

		return null;
	}

	public function __get($name)
	{
		$name = $this->getMap()->decodeCamelFieldName($name);
		if($name)
		{
			return $this[$name];
		}

		return null;
	}

	public function __set($name, $value)
	{
		$name = $this->getMap()->decodeCamelFieldName($name);
		if($name)
		{
			$this[$name] = $value;
		}
	}
}