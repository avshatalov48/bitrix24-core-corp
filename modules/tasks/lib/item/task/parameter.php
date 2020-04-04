<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Tasks\Item\Task;

use Bitrix\Tasks\Internals\Task\ParameterTable;
use Bitrix\Tasks\Item\Task;

final class Parameter extends \Bitrix\Tasks\Item\SubItem
{
	protected static function getParentConnectorField()
	{
		return 'TASK_ID';
	}

	public static function getDataSourceClass()
	{
		return ParameterTable::getClass();
	}

	public static function getParentClass()
	{
		return Task::getClass();
	}
}