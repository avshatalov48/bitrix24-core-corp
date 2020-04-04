<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 * 
 * @access private
 *
 * Each method you put here you`ll be able to call as ENTITY_NAME.METHOD_NAME via AJAX and\or REST, so be careful.
 */

namespace Bitrix\Tasks\Dispatcher\PublicAction\Task;

use \Bitrix\Tasks\DependenceTable;
use \Bitrix\Tasks\DB\Tree;
use Bitrix\Tasks\Util\User;

final class Dependence extends \Bitrix\Tasks\Dispatcher\RestrictedAction
{
	/**
	 * Add a new dependence between two tasks
	 */
	public function add($taskIdFrom, $taskIdTo, $linkType)
	{
		try
		{
			$task = new \CTaskItem($taskIdTo, User::getId());
			$task->addDependOn($taskIdFrom, $linkType);
		}
		catch(Tree\Exception $e)
		{
			$this->errors->add('ILLEGAL_NEW_LINK', \Bitrix\Tasks\Dispatcher::proxyExceptionMessage($e));
		}

		return array();
	}

	/**
	 * Delete an existing dependence between two tasks
	 */
	public function delete($taskIdFrom, $taskIdTo)
	{
		try
		{
			$task = new \CTaskItem($taskIdTo, User::getId());
			$task->deleteDependOn($taskIdFrom);
		}
		catch(Tree\Exception $e)
		{
			$this->errors->add('ILLEGAL_LINK', \Bitrix\Tasks\Dispatcher::proxyExceptionMessage($e));
		}

		return array();
	}
}