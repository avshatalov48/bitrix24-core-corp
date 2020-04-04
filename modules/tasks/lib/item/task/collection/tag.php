<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Tasks\Item\Task\Collection;

use Bitrix\Tasks\Util\Type;
use Bitrix\Tasks\Item;

final class Tag extends Item\Collection
{
	protected static function getItemClass()
	{
		return Item\Task\Tag::getClass();
	}

	/**
	 * @param $value
	 * @param Item $item
	 */
	public function updateValue($value, $item)
	{
		// todo
	}

	public function joinNames($separator = ',')
	{
		$result = array();
		foreach($this->values as $item)
		{
			if($item['NAME'] != '')
			{
				$result[] = $item['NAME'];
			}
		}

		return implode($separator, $result);
	}
}