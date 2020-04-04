<?
/**
 * This class could be changed (removed, renamed, relocated) in any time, so do not use it in public code
 *
 * @internal
 * @access private
 */

namespace Bitrix\Tasks\Processor\Task\Scheduler;

use Bitrix\Tasks\Processor\Task\Scheduler\Result\Impact;

class Relation
{
	protected $task = null;
	protected $parentTask = null;

	public function setTask($task)
	{
		$this->task = $task;
	}

	/**
	 * @return Impact
	 */
	public function getTask()
	{
		return $this->task;
	}

	/**
	 * @param Impact $task
	 */
	public function setParentTask($task)
	{
		if($task)
		{
			$this->parentTask = $task;
		}
	}

	/**
	 * @return Impact
	 */
	public function getParentTask()
	{
		return $this->parentTask;
	}

	/**
	 * @return int
	 */
	public function getTaskId()
	{
		return $this->getTask()->getId();
	}

	/**
	 * @return int
	 */
	public function getParentTaskId()
	{
		if($this->parentTask !== null)
		{
			return $this->parentTask->getId();
		}

		return 0;
	}
}