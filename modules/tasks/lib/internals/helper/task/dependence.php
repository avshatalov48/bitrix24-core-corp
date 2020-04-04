<?
/**
 * @internal
 */

namespace Bitrix\Tasks\Internals\Helper\Task;

use Bitrix\Tasks\Internals\DataBase\Structure\ClosureTree;
use Bitrix\Tasks\Internals\Task\DependenceTable;

final class Dependence extends ClosureTree
{
	protected static function getDataController()
	{
		return DependenceTable::getClass();
	}

	protected static function getNodeColumnName()
	{
		return 'TASK_ID';
	}

	protected static function getParentNodeColumnName()
	{
		return 'PARENT_TASK_ID';
	}
}