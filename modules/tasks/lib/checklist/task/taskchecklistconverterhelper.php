<?php
namespace Bitrix\Tasks\CheckList\Task;

use Bitrix\Tasks\CheckList\Internals\CheckListConverterHelper;
use Bitrix\Tasks\Update\TaskCheckListConverter;

/**
 * Class TaskCheckListConverterHelper
 *
 * @package Bitrix\Tasks\CheckList\Task
 */
class TaskCheckListConverterHelper extends CheckListConverterHelper
{
	protected static $facade = TaskCheckListFacade::class;

	/**
	 * @return string
	 */
	protected static function getNeedOptionName()
	{
		return TaskCheckListConverter::$needOptionName;
	}
}