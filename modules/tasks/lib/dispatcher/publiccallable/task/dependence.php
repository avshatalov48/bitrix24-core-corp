<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 * 
 * @access private
 * 
 * Each public method you put here you`ll be able to call as ENTITY_NAME.METHOD_NAME, so be careful.
 */

namespace Bitrix\Tasks\Dispatcher\PublicCallable\Task;

use \Bitrix\Tasks\DependenceTable;
use \Bitrix\Tasks\DB\Tree;

final class Dependence extends \Bitrix\Tasks\Dispatcher\PublicCallable
{
	/**
	 * Add a new dependence between two tasks
	 */
	public function add($taskIdFrom, $taskIdTo, $linkType)
	{
		global $USER;

		try
		{
			$task = new \CTaskItem($taskIdTo, $USER->GetId());
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
		global $USER;

		try
		{
			$task = new \CTaskItem($taskIdTo, $USER->GetId());
			$task->deleteDependOn($taskIdFrom, $linkType);
		}
		catch(Tree\Exception $e)
		{
			$this->errors->add('ILLEGAL_LINK', \Bitrix\Tasks\Dispatcher::proxyExceptionMessage($e));
		}

		return array();
	}
}