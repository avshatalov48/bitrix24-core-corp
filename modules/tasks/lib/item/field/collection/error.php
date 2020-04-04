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

namespace Bitrix\Tasks\Item\Field\Collection;

use Bitrix\Tasks\Util\Type;
use Bitrix\Tasks\Util;

class Error extends \Bitrix\Tasks\Item\Field\Collection
{
	protected static function getItemCollectionClass()
	{
		return Util\Error\Collection::getClass();
	}

	public function translateValueFromDatabase($value, $key, $item)
	{
		return $this->createValue(Type::unSerializeArray($value), $key, $item);
	}

	public function translateValueToDatabase($value, $key, $item)
	{
		if(\Bitrix\Tasks\Util\Error\Collection::isA($value))
		{
			/** @var \Bitrix\Tasks\Util\Error\Collection $value */
			$value = $value->getArrayMeta(); // get only CODE, TEXT and TYPE: skip DATA, because of its quite agnostic content
		}
		else
		{
			$value = array();
		}

		return Type::serializeArray($value); // translate error collection to array, and each its sub-object too
	}

	public function translateValueFromOutside($value, $key, $item)
	{
		$collectionClass = static::getItemCollectionClass();

		// error collection it is
		if($collectionClass::isA($value))
		{
			return $value;
		}

		// it must be a serialized array
		if(is_string($value))
		{
			$value = Type::unSerializeArray($value);
		}

		return $this->createValue($value, $key, $item);
	}

	public function createValue($value, $key, $item)
	{
		$collectionClass = static::getItemCollectionClass();

		if($collectionClass::isA($value))
		{
			return $value;
		}

		if(!is_array($value))
		{
			$value = (array) $value;
		}

		$result = new $collectionClass();
		if(is_array($value))
		{
			foreach($value as $error)
			{
				$result->add($error['CODE'], $error['MESSAGE'], $error['TYPE']);
			}
		}

		return $result;
	}
}