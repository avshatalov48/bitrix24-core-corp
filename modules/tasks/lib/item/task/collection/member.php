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
use Bitrix\Tasks\Item\SubItem;

final class Member extends Item\Collection
{
	protected static function getItemClass()
	{
		return Item\Task\Member::getClass();
	}

	/**
	 * @param $valuePart
	 * @param $valueType
	 * @param Item $item
	 */
	public function updateValuePart($valuePart, $valueType, $item)
	{
		$itemClass = static::getItemClass();

		$this->delete(array('=TYPE' => $valueType)); // remove all the previous of this type

		if(Type::isIterable($valuePart))
		{
			foreach($valuePart as $k => $v)
			{
				if($v === null) // there were a null-ed field
				{
					continue;
				}

				/** @var SubItem $subItem */
				$subItem = new $itemClass(array(
					'TASK_ID' => $item->getId(),
					'USER_ID' => $v,
					'TYPE' => $valueType,
				), $item->getUserId());
				$subItem->setParent($item);

				$this->push($subItem);
			}
		}
	}

	public function getUserIds()
	{
		$ids = array();
		foreach($this->values as $member)
		{
			$ids[$member['USER_ID']] = true;
		}

		return array_keys($ids);
	}
}