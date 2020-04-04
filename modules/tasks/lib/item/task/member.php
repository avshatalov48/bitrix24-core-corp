<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Tasks\Item\Task;

use Bitrix\Tasks\Internals\Task\MemberTable;

final class Member extends \Bitrix\Tasks\Item\SubItem
{
	protected static function getParentConnectorField()
	{
		return 'TASK_ID';
	}

	public static function getDataSourceClass()
	{
		return MemberTable::getClass();
	}

	public static function getCollectionClass()
	{
		return \Bitrix\Tasks\Item\Task\Collection\Member::getClass();
	}
}