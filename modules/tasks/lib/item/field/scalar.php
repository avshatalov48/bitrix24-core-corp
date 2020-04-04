<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @access private
 * @internal
 */

namespace Bitrix\Tasks\Item\Field;

use Bitrix\Main\NotImplementedException;
use Bitrix\Tasks\Item;
use Bitrix\Tasks\Util\Assert;
use Bitrix\Tasks\Util\Result;

class Scalar implements \ArrayAccess
{
	const SOURCE_TABLET = 1; // rename to VALUE_SOURCE_DB_TABLET ???
	const SOURCE_UF = 2;
	const SOURCE_CUSTOM = 3;

	const VALUE_SOURCE_DB = 1;
	const VALUE_SOURCE_OUTSIDE = 2;

	private $name = '';
	private $source = 0;
	private $title = '';
	protected $default = null;

	private $dbReadable = true;
	private $dbWritable = true;
	private $dbName = null;
	private $internallyCacheable = true;
	private $sortable = false;
	private $filterable = false;

	public function __construct(array $parameters)
	{
		$this->setName($parameters['NAME']);
		$this->setSource($parameters['SOURCE']);

		if($parameters['SOURCE'] != static::SOURCE_CUSTOM)
		{
			if(array_key_exists('DB_NAME', $parameters))
			{
				$this->setDBName($parameters['DB_NAME']);
			}

			if(array_key_exists('DB_READABLE', $parameters))
			{
				$this->setDBReadable($parameters['DB_READABLE']);
			}
			if(array_key_exists('DB_WRITABLE', $parameters))
			{
				$this->setDBWritable($parameters['DB_WRITABLE']);
			}
		}

		if(array_key_exists('DEFAULT', $parameters))
		{
			$this->setDefaultValue($parameters['DEFAULT']);
		}
		if(array_key_exists('TITLE', $parameters))
		{
			$this->setTitle($parameters['TITLE']);
		}
		if(array_key_exists('OFFSET_GET_CACHEABLE', $parameters))
		{
			$this->setOffsetGetCacheable($parameters['OFFSET_GET_CACHEABLE']);
		}

		// other (will be useful when implementing getlist())
		if(array_key_exists('FILTERABLE', $parameters))
		{
			$this->setFilterable($parameters['FILTERABLE']);
		}
		if(array_key_exists('SORTABLE', $parameters))
		{
			$this->setSortable($parameters['SORTABLE']);
		}
	}

	public function getName()
	{
		return $this->name;
	}

	public function setName($name)
	{
		$this->name = Assert::expectStringNotNull($name, 'Name not defined');
	}

	public function isCamelName($camelName)
	{
		return ToLower(str_replace('_', '', $this->name)) == ToLower(trim((string) $camelName));
	}

	public function setDBName($name)
	{
		$this->dbName = Assert::expectStringNotNull($name, 'DB name is illegal');
	}

	public function getDBName()
	{
		return $this->dbName !== null ? $this->dbName : $this->name;
	}

	public function getTitle()
	{
		return $this->title != '' ? $this->title : $this->getName();
	}

	public function setTitle($title)
	{
		$this->title = $title;
	}

	public function isDBReadable()
	{
		return $this->dbReadable;
	}

	public function setDBReadable($flag)
	{
		$this->dbReadable = !!$flag;
	}

	public function isDBWritable()
	{
		return $this->dbWritable;
	}

	public function setDBWritable($flag)
	{
		$this->dbWritable = !!$flag;
	}

	public function setOffsetGetCacheable($flag)
	{
		$this->internallyCacheable = !!$flag;
	}

	public function isCacheable()
	{
		return $this->internallyCacheable;
	}

	public function isSortable()
	{
		return $this->sortable;
	}

	public function setSortable($flag)
	{
		$this->sortable = !!$flag;
	}

	public function isFilterable()
	{
		return $this->filterable;
	}

	public function setFilterable($flag)
	{
		$this->filterable = !!$flag;
	}

	public function isSourceTablet()
	{
		return $this->source == static::SOURCE_TABLET;
	}

	public function isSourceUserField()
	{
		return $this->source == static::SOURCE_UF;
	}

	public function isSourceCustom()
	{
		return $this->source == static::SOURCE_CUSTOM;
	}

	public function setSource($source)
	{
		$this->source = static::SOURCE_TABLET;
		if($source)
		{
			$this->source = Assert::expectEnumerationMember($source, array(
				static::SOURCE_TABLET,
				static::SOURCE_UF,
				static::SOURCE_CUSTOM,
			));
		}
	}

	public function getSource()
	{
		return $this->source;
	}

	/**
	 * @param String $key
	 * @param Item $item
	 * @return bool
	 */
	public function hasDefaultValue($key, $item)
	{
		return $this->default !== null;
	}

	/**
	 * @param String $key
	 * @param Item $item
	 * @return mixed
	 */
	public function getDefaultValue($key, $item)
	{
		return $this->createValue($this->default, $key, $item);
	}

	/**
	 * @param mixed $value
	 * @return void
	 */
	public function setDefaultValue($value)
	{
		$this->default = $value;
	}

	/**
	 * @param string $key
	 * @param Item $item
	 * @param array $parameters
	 * @return mixed|null
	 */
	public function getValue($key, $item, array $parameters = array())
	{
		if($item->containsKey($key))
		{
			return $item->offsetGetDirect($key);
		}

		$value = null;

		if(!$item->getId())
		{
			// this is a new entity, and no value in cache, so get the default one (read "just like from database")
			$value = $this->getDefaultValue($key, $item);
		}
		else
		{
			$value = $this->readValueFromDatabase($key, $item);
		}

		// cache this field, if can
		if($this->isCacheable())
		{
			$item->offsetSetDirect($key, $value);
		}

		return $value;
	}

	/**
	 * @param mixed $value
	 * @param string $key
	 * @param Item $item
	 * @param array $parameters
	 * @return mixed
	 */
	public function setValue($value, $key, $item, array $parameters = array())
	{
		$value = $this->makeValueSafe($value, $key, $item, $parameters);

		if($this->isCacheable())
		{
			$keepExisting = array_key_exists('KEEP_EXISTING_VALUE', $parameters) && $parameters['KEEP_EXISTING_VALUE'] === true;

			if(!$item->containsKey($key) || !$keepExisting)
			{
				$item->offsetSetDirect($key, $value);
			}
		}

		return $value;
	}

	/**
	 * @param mixed $value
	 * @param string $key
	 * @param Item $item
	 * @param array $parameters
	 * @return bool
	 */
	public function prepareValue($value, $key, $item, array $parameters = array())
	{
		return true;
	}

	/**
	 * @param mixed $value
	 * @param string $key
	 * @param Item $item
	 * @param array $parameters
	 * @return bool
	 */
	public function checkValue($value, $key, $item, array $parameters = array())
	{
		return true;
	}

	/**
	 * @param array $parameters
	 * @return null|Result
	 */
	protected static function obtainResultInstance(array $parameters)
	{
		$result = null;
		if(array_key_exists('RESULT', $parameters) && $parameters['RESULT'] instanceof Result)
		{
			$result = $parameters['RESULT'];
		}

		return $result;
	}

	/**
	 * Performs custom reading from database, for SOURCE_TYPE == custom
	 *
	 * @param $key
	 * @param Item $item
	 * @return null
	 */
	public function readValueFromDatabase($key, $item)
	{
		$value = null; // read it from somewhere

		return $this->translateValueFromDatabase($value, $key, $item);
	}

	/**
	 * Performs custom saving to database, for SOURCE_TYPE == custom
	 *
	 * @param $value
	 * @param $key
	 * @param Item $item
	 * @return Result
	 */
	public function saveValueToDataBase($value, $key, $item)
	{
		//$value = $this->translateValueToDatabase($value, $key, $item);

		// then save to somewhere

		return new Result();
	}

	public function translateValueFromDatabase($value, $key, $item)
	{
		return $this->createValue($value, $key, $item);
	}

	public function updateRelatedFieldValues($value, $key, $item)
	{
	}

	public function getMergedValueSafe($value, $key, $item, array $parameters = array())
	{
		// $value is already internalized here
		return $value;
	}

	/**
	 * Returns value that is suitable to store in database for this field
	 *
	 * @param $value
	 * @param $item
	 * @return mixed
	 */
	public function translateValueToDatabase($value, $key, $item)
	{
		return $value;
	}

	/**
	 * Returns safe and parsed value came outside, that can be used internally
	 *
	 * @param $value
	 * @param $key
	 * @param Item $item
	 * @return mixed
	 */
	public function translateValueFromOutside($value, $key, $item)
	{
		return $this->createValue($value, $key, $item);
	}

	/**
	 * Returns value that definitely represents the required type
	 *
	 * @param $value
	 * @param $key
	 * @param Item $item
	 * @return mixed
	 */
	public function createValue($value, $key, $item)
	{
		return $value;
	}

	protected function makeValueSafe($value, $key, $item, array $parameters = array())
	{
		if($parameters['VALUE_SOURCE'] == static::VALUE_SOURCE_DB)
		{
			$value = $this->translateValueFromDatabase($value, $key, $item);
		}
		elseif($parameters['VALUE_SOURCE'] == static::VALUE_SOURCE_OUTSIDE)
		{
			$value = $this->translateValueFromOutside($value, $key, $item);
		}
		// else it is internal

		return $value;
	}

	public function offsetExists($offset)
	{
		return $offset == 'SOURCE' || $offset == 'NAME' || $offset == 'TITLE';
	}

	public function offsetGet($offset)
	{
		if($offset == 'SOURCE')
		{
			return $this->getSource();
		}
		if($offset == 'NAME')
		{
			return $this->getName();
		}
		if($offset == 'TITLE')
		{
			return $this->getTitle();
		}

		return null;
	}

	public function offsetSet($offset, $value)
	{
		throw new NotImplementedException();
	}

	public function offsetUnset($offset)
	{
		throw new NotImplementedException();
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