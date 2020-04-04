<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Tasks\Item\Task;

use Bitrix\Tasks\Internals\Task\ProjectDependenceTable;

final class ProjectDependence extends \Bitrix\Tasks\Item\SubItem
{
	protected static function getParentConnectorField()
	{
		return 'TASK_ID';
	}

	public static function getDataSourceClass()
	{
		return ProjectDependenceTable::getClass();
	}

	protected static function getBindCondition($parentId)
	{
		return parent::getBindCondition($parentId) + array('=DIRECT' => '1');
	}
}