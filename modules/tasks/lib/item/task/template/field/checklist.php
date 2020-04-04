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

class CheckList extends \Bitrix\Tasks\Item\Task\Field\CheckList
{
	protected static function getItemClass()
	{
		return \Bitrix\Tasks\Item\Task\Template\CheckList::getClass();
	}
}