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

class ProjectDependence extends \Bitrix\Tasks\Item\Field\Collection\Item
{
	protected static function getItemClass()
	{
		return \Bitrix\Tasks\Item\Task\ProjectDependence::getClass();
	}

	/**
	 * todo: implement this properly
	 * @see \Bitrix\Tasks\Manager\Task\ProjectDependence
	 *
	 * @param $value
	 * @param $key
	 * @param \Bitrix\Tasks\Item $item
	 * @return \Bitrix\Tasks\Util\Result
	 */
	public function saveValueToDataBase($value, $key, $item)
	{
		return new \Bitrix\Tasks\Util\Result();
	}
}