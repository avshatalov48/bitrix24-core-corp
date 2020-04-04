<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 * 
 * @access private
 * 
 * Each method you put here you`ll be able to call as ENTITY_NAME.METHOD_NAME, so be careful.
 */

namespace Bitrix\Tasks\Dispatcher\PublicCallable\Task;

final class Favorite extends \Bitrix\Tasks\Dispatcher\PublicCallable
{
	/**
	 * Add a task to users own favorite list
	 */
	public function add($taskId)
	{
		global $USER;

		$result = array();

		if($taskId = $this->checkTaskId($taskId))
		{
			// user can add a task ONLY to his OWN favorite-list
			$task = new \CTaskItem($taskId, $USER->GetId());
			$task->addToFavorite();
		}

		return $result;
	}

	/**
	 * Remove a task from users own favorite list
	 */
	public function delete($taskId)
	{
		global $USER;

		$result = array();

		if($taskId = $this->checkTaskId($taskId))
		{
			// user can add a task ONLY to his OWN favorite-list
			$task = new \CTaskItem($taskId, $USER->GetId());
			$task->deleteFromFavorite();
		}

		return $result;
	}
}