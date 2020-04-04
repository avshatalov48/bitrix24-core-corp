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

namespace Bitrix\Tasks\Item\Task\Field;

class CheckList extends \Bitrix\Tasks\Item\Field\Collection\Item
{
	protected static function getItemClass()
	{
		return \Bitrix\Tasks\Item\Task\CheckList::getClass();
	}

	/**
	 * @param \Bitrix\Tasks\Item\Task\Collection\CheckList $value
	 * @param $key
	 * @param $item
	 */
	protected function onBeforeSaveToDataBase($value, $key, $item)
	{
		parent::onBeforeSaveToDataBase($value, $key, $item);
		if($value) // actually, there should be more strict check, for isA()
		{
			$value->sealSortIndex();
		}
	}
}