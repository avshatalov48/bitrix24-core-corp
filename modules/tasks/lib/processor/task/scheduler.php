<?
/**
 * This class could be changed (removed, renamed, relocated) in any time, so do not use it in public code
 *
 * @internal
 * @access private
 */

namespace Bitrix\Tasks\Processor\Task;

use Bitrix\Main\NotImplementedException;
use Bitrix\Tasks\Internals\RunTime;

use Bitrix\Tasks\Processor\Task\Result;
use Bitrix\Tasks\Processor\Task\Scheduler\Result\Impact;
use Bitrix\Tasks\Processor\Task\Scheduler\RelationManager;
use Bitrix\Tasks\Processor\Task\Scheduler\RelationManager\Project;
use Bitrix\Tasks\Processor\Task\Scheduler\RelationManager\SubTask;

use Bitrix\Tasks\Item;

final class Scheduler extends \Bitrix\Tasks\Processor
{
	protected $queue = array();

	/**
	 * @var Impact[]
	 */
	protected $met = array();
	protected $processors = array();

	/**
	 * Get affected tasks, going through Gantt dependence and sub-task dependence
	 *
	 * @param $id int task
	 * @param mixed[] changed fields
	 * @param mixed[] $settings
	 * @return \Bitrix\Tasks\Processor\Task\Result
	 */
	public function processEntity($id, $data = array(), array $settings = array())
	{
		$result = parent::processEntity($id, $data, $settings);

		$id = intval($id);

		// todo: Impact class is TEMPORAL, it should be replaced with (or at least inherited from) \Bitrix\Tasks\Item\Task when ready
		// we set impact on $id regardless of its location in project or being a sub-task
		$taskImpact = new Impact($id, $this->getUserId());

		if(is_array($data))
		{
			$taskImpact->setDataUpdated($data);
		}

		// now we must see if impact on $id affects other tasks...
		$inSubTask = SubTask::isTaskBelong($id, $data);
		$inProject = Project::isTaskBelong($id, $data);

		$this->addImpact($taskImpact);

		if($inSubTask || $inProject)
		{
			if($inSubTask)
			{
				$this->pushQueue($id, $this->getRelationProcessor('S'));
			}
			if($inProject)
			{
				$this->pushQueue($id, $this->getRelationProcessor('P'));
			}

			$times = 0;
			while(count($this->queue) && $times < 10000)
			{
				$times++;
				$next = array_shift($this->queue);

				/** @var \Bitrix\Tasks\Processor\Task\Scheduler\RelationManager\Project|\Bitrix\Tasks\Processor\Task\Scheduler\RelationManager\SubTask $nextProcessor */
				$nextProcessor = $next['PROCESSOR'];

				$metKey = $next['ID'].'-'.$nextProcessor->getCode();
				if($this->met[$metKey])
				{
					continue; // just do not go the same way twice
				}
				$this->met[$metKey] = true;

				/** @var \Bitrix\Tasks\Processor\Task\Scheduler\Result\Impact $impact */
				$impact = $this->getImpactById($next['ID']);

				$processorSettings = array();
				if($impact->getId() == $id) // root impact is being processed
				{
					$processorSettings['MODE'] = $settings['MODE'];
				}

				$nextProcessor->processTask($impact, $result, $processorSettings);
			}

			if($times >= 10000)
			{
				$result->addError('ILLEGAL_STRUCTURE.DEPTH', 'Insane tree depth faced');
			}
		}

		$result->setData($this->affected);

		return $result;
	}

	/**
	 * @param int|mixed[] $task
	 * @return Impact
	 */
	public function defineTaskDates($task)
	{
		$result = new Result();

		$task = new Impact($task, $this->getUserId());

		$startDatePlanSet = (string) $task['START_DATE_PLAN'] != '';
		$endDatePlanSet = (string) $task['END_DATE_PLAN'] != '';

		if($startDatePlanSet && $endDatePlanSet)
		{
			return $result;
		}

		$task->resetDates();

		$result->setData(array($task));

		return $result;
	}

	public function pushQueue($id, $handler)
	{
		$this->queue[] = array(
			'ID' => $id,
			'PROCESSOR' => $handler,
		);
	}

	protected function reset()
	{
		$this->queue = array();
		$this->affected = array();
		$this->met = array();
	}

	public function getRelationProcessor($code)
	{
		if(!$this->processors[$code])
		{
			$instance = null;
			if($code == 'S')
			{
				$instance = new SubTask();
			}
			elseif($code == 'P')
			{
				$instance = new Project();
			}
			else
			{
				throw new NotImplementedException();
			}

			$instance->setScheduler($this);
			$this->processors[$code] = $instance;
		}

		return $this->processors[$code];
	}
}