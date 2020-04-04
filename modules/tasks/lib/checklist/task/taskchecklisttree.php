<?php
namespace Bitrix\Tasks\CheckList\Task;

use Bitrix\Tasks\CheckList\Internals\CheckListTree;
use Bitrix\Tasks\Internals\Task\CheckListTreeTable;

/**
 * Class CheckListTree
 *
 * @package Bitrix\Tasks\CheckList\Task
 */
class TaskCheckListTree extends CheckListTree
{
	/**
	 * @return string
	 */
	public static function getDataController()
	{
		return CheckListTreeTable::getClass();
	}
}