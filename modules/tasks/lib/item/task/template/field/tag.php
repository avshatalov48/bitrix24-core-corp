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

namespace Bitrix\Tasks\Item\Task\Template\Field;

use Bitrix\Tasks\Util\Type;
use Bitrix\Tasks\Util\Collection;
use Bitrix\Tasks\Item\Exporter\Canonical;

class Tag extends \Bitrix\Tasks\Item\Field\Collection
{
	public function translateValueFromDatabase($value, $key, $item)
	{
		$value = static::filterArray(Type::unSerializeArray($value));

		return $this->createValue($value, $key, $item);
	}

	public function translateValueToDatabase($value, $key, $item)
	{
		$tags = array();
		foreach($value as $tag)
		{
			$tags[] = $tag['NAME'];
		}

		return Type::serializeArray($tags);
	}

	public function translateValueFromOutside($value, $key, $item)
	{
		if(is_string($value)) // comma-separated tags
		{
			$value = explode(',', $value);
		}

		if(Type::isIterable($value)) // array
		{
			if(is_array($value))
			{
				foreach($value as $k => $v)
				{
					if(!$v)
					{
						unset($value[$k]);
						continue;
					}

					if(is_string($v))
					{
						$value[$k] = array(
							'NAME' => $v
						);
					}
				}
			}
		}
		else
		{
			$value = array();
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

		$createdBy = $item['CREATED_BY'];
		if(!$createdBy)
		{
			$createdBy = $item->getUserId();
		}

		// imitate SE_TAG of \Bitrix\Tasks\Item\Task
		foreach($value as $i => $tag)
		{
			$value[$i] = new Collection(array(
				'NAME' => $tag['NAME'],
				'USER_ID' => $createdBy,
				'TEMPLATE_ID' => $item->getId(),
			));
		}

		return new $collectionClass($value);
	}

	public function export($exporter = null)
	{
		if($exporter === null)
		{
			$exporter = new Canonical(); // todo: object pool here
		}

		return $exporter->export($this);
	}

	private static function filterArray($value)
	{
		$newValue = array();
		$value = array_unique(array_map('trim', $value));
		foreach($value as $tag)
		{
			$newValue[] = array(
				'NAME' => $tag
			);
		}

		return $newValue;
	}
}