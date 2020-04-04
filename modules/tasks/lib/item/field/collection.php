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

use Bitrix\Tasks\Exception;
use Bitrix\Tasks\Util;

class Collection extends Scalar
{
	public function __construct(array $parameters)
	{
		if(!array_key_exists('SOURCE', $parameters))
		{
			$parameters['SOURCE'] = static::SOURCE_CUSTOM;
		}

		parent::__construct($parameters);
	}

	/**
	 * @return Util\Collection
	 */
	protected static function getItemCollectionClass()
	{
		return Util\Collection::getClass();
	}

	public function translateValueFromDatabase($value, $key, $item)
	{
		return $this->createValue($value, $key, $item);
	}

	public function translateValueFromOutside($value, $key, $item)
	{
		if(Util\Type::isIterable($value))
		{
			// drop broken
			foreach($value as $k => $v)
			{
				if((string) $v == '')
				{
					unset($value[$k]);
				}
			}
		}

		return $this->createValue($value, $key, $item);
	}

	public function getDefaultValue($key, $item)
	{
		return $this->createValue($this->default === null ? array() : $this->default, $key, $item);
	}

	public function createValue($value, $key, $item)
	{
		$collectionClass = static::getItemCollectionClass();

		if($collectionClass::isA($value))
		{
			return $value; // todo: clone() here?
		}

		if($value == null)
		{
			$value = array();
		}
		elseif(!is_array($value))
		{
			$value = (array) $value;
		}

		return new $collectionClass($value);
	}
}