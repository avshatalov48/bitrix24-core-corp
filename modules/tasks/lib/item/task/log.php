<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Tasks\Item\Task;

use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\Type;
use Bitrix\Tasks\Util\User;

final class Log extends \Bitrix\Tasks\Item\SubItem
{
	protected static function getParentConnectorField()
	{
		return 'TASK_ID';
	}

	public static function getDataSourceClass()
	{
		return \Bitrix\Tasks\Internals\Task\LogTable::getClass();
	}
}