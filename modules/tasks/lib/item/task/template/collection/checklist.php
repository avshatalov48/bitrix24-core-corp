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

namespace Bitrix\Tasks\Item\Task\Template\Collection;

class CheckList extends \Bitrix\Tasks\Item\Task\Collection\CheckList
{
	protected static function getItemClass()
	{
		return '\\Bitrix\\Tasks\\Item\\Task\\Template\\CheckList';
	}

	protected static function getSortColumnName()
	{
		return 'SORT';
	}

	protected static function getCheckedColumnName()
	{
		return 'CHECKED';
	}
}
