<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @internal
 */

namespace Bitrix\Tasks\Item\Exporter;

use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Item;
use Bitrix\Tasks\Item\Collection;
use Bitrix\Tasks\Util\Type\Dictionary;
use Bitrix\Tasks\Util\Type\Structure;
use Bitrix\Tasks\Util;

final class Canonical
{
	protected $parameters = array();

	public function __construct(array $parameters = array())
	{
		$this->parameters = $parameters;
	}

	/**
	 * @param Item|Util\Collection $item
	 * @param array|string $select
	 * @return array
	 */
	public function export($item, $select = array())
	{
		$result = array();

		if(Item::isA($item))
		{
			$data = $item->getData($select);
		}
		else
		{
			$data = $item;
		}

		foreach($data as $k => $v)
		{
			if(Item::isA($v)) // go deeper into exporting item
			{
				$result[$k] = $this->export($v, $select);
			}
			elseif(Util\Collection::isA($v)) // go deeper into exporting item collection
			{
				$result[$k] = $this->export($v, $select);
			}
			else
			{
				$result[$k] = $this->exportTypeValue($v, $item);
			}
		}

		return $result;
	}

	/**
	 * @param DateTime|Dictionary|Structure|mixed[]|mixed|null $v
	 * @param $item
	 * @return null|string
	 */
	protected function exportTypeValue($v, $item)
	{
		if(Dictionary::isA($v)) // all dictionaries goes to arrays
		{
			return $v->toArray(); // todo: there also may be sub-objects inside, deal with them...
		}
		elseif(is_object($v))
		{
			if(Structure::isA($v))
			{
				return $v->get();
			}
			elseif($v instanceof DateTime)
			{
				// todo: it is better to export dates in ISO format, with timezone offset
				return $v->toString();
			}
			elseif(method_exists($v, 'toString'))
			{
				return (string) $v;
			}
			else
			{
				return null;
			}
		}
		elseif(is_array($v)) // arrays exported as-is // todo: there also may be sub-objects inside, deal with them...
		{
			return $v;
		}
		else
		{
			// todo: there may be a date represented as string, so we need to ask $item->getFieldController() about an actual type and then convert
			// todo: to ISO string if needed
			return (string) $v;
		}
	}
}