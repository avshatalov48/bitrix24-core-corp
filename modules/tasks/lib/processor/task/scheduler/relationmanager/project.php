<?
/**
 * This class could be changed (removed, renamed, relocated) in any time, so do not use it in public code
 *
 * @internal
 * @access private
 */

namespace Bitrix\Tasks\Processor\Task\Scheduler\RelationManager;

use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Internals\RunTime;
use Bitrix\Tasks\Internals\Task\ProjectDependenceTable;
use Bitrix\Tasks\Processor\Task\Scheduler\Result\Impact;
use Bitrix\Tasks\Processor\Task\Scheduler\RelationManager\Project\Relation;
use Bitrix\Tasks\Processor\Task\Result;

final class Project extends \Bitrix\Tasks\Processor\Task\Scheduler\RelationManager
{
	public static function getCode()
	{
		return 'P';
	}

	/**
	 * @param \Bitrix\Tasks\Processor\Task\Scheduler\Result\Impact $rootImpact
	 * @param Result $result
	 * @param mixed[] $settings
	 * @throws \Bitrix\Main\NotImplementedException
	 * @return void
	 */
	public function processTask($rootImpact, $result, array $settings = array())
	{
		if(!$rootImpact)
		{
			return;
		}

		$rootImpact->setAsHead();

		$id = $rootImpact->getId();
		if($id <= 0)
		{
			return;
		}

		//_print_r('============== Process project on '.$id);

		// get task sub-tree
		// do not attach sql rights checking, because it is quite heavy still
		// instead, use php checking below

		// todo: getSubTree() should became deprecated
		$res = ProjectDependenceTable::getSubTree($id, Runtime::apply(array(
			'runtime' => array(
				new ReferenceField(
					'PARENT',
					ProjectDependenceTable::getEntity(),
					array(
						'=this.TASK_ID' => 'ref.TASK_ID',
						'=ref.DIRECT' => array('?', '1'),
					)
				)
			),
			'select' => array(
				// relation data
				'TASK_ID',
				'EFFECTIVE_PARENT_ID' => 'PARENT.DEPENDS_ON_ID',
				'TYPE',

				// task data
				// todo: use Impact::getBaseMixin() here
				'TITLE' => 'TASK.TITLE', // tmp
				'START_DATE_PLAN' => 'TASK.START_DATE_PLAN',
				'END_DATE_PLAN' => 'TASK.END_DATE_PLAN',
				'CREATED_DATE' => 'TASK.CREATED_DATE',
				'MATCH_WORK_TIME' => 'TASK.MATCH_WORK_TIME',
				'ALLOW_CHANGE_DEADLINE' => 'TASK.ALLOW_CHANGE_DEADLINE',
				'DURATION_TYPE' => 'TASK.DURATION_TYPE',
				'DURATION_PLAN' => 'TASK.DURATION_PLAN',
				// task fields for php rights checking
				'RESPONSIBLE_ID' => 'TASK.RESPONSIBLE_ID',
				'CREATED_BY' => 'TASK.CREATED_BY',
				'GROUP_ID' => 'TASK.GROUP_ID',
				'STATUS' => 'TASK.STATUS',
			),
		), array(
			RunTime\Task::getTask(array('REF_FIELD' => 'TASK_ID', 'JOIN_TYPE' => 'inner')),
		)), array('INCLUDE_SELF' => true));

		$tasks = array();
		$parentId = 0;
		while($item = $res->fetch())
		{
			$taskId = $item['TASK_ID'];
			unset($item['TASK_ID']);
			$item['ID'] = $taskId;

			$tasks[$item['EFFECTIVE_PARENT_ID']][$item['ID']] = $item;

			if($taskId == $id)
			{
				$parentId = $item['EFFECTIVE_PARENT_ID'];
			}

			//_print_r('----------------- subtask is: '.$item['PARENT_TASK_ID'].' -> '.$item['TASK_ID'].' '.$item['TITLE']);
		}

		// here we need to skip task which we can not move (and subtrees of such tasks)
		$tasks = $this->checkCoherence($parentId, $tasks, $result);

		$relations = array();
		$relationsFlat = array();
		if($result->isSuccess())
		{
			$scheduler = $this->getScheduler();
			foreach($tasks as $task)
			{
				// root task was already rescheduled ($rootImpact)
				if($task['ID'] == $id)
				{
					continue;
				}

				// todo: Impact class is TEMPORAL, it should be replaced with (or at least inherited from) \Bitrix\Tasks\Item\Task when ready
				$taskImpact = new Impact($task, $this->getUserId());

				$relation = new Relation($task);
				$relation->setTask($taskImpact);
				$relation->setParentTask($scheduler->getImpactById($task['EFFECTIVE_PARENT_ID'])); // update lag here will happen

				$relationsFlat[] = $relation;
				$relations[$relation->getParentTaskId()][$relation->getTaskId()] = $relation;

				$scheduler->pushQueue($taskImpact->getId(), $scheduler->getRelationProcessor('S'));
				$scheduler->addImpact($taskImpact);
			}

			$this->updateRelatedTasks($id, $relations);
			$this->dumpRelations($relationsFlat);
		}
	}

	public static function isTaskBelong($id, $data = array())
	{
		return $id && ProjectDependenceTable::checkItemLinked($id);
	}

	/**
	 * @param $id
	 * @param Relation[][] $relations
	 */
	private function updateRelatedTasks($id, array $relations)
	{
		if(is_array($relations[$id]))
		{
			foreach($relations[$id] as $relation)
			{
				if($relation->isProcessed()) // already been here
				{
					continue;
				}

				$toTaskId = $relation->getTaskId();
				/** @var Impact $toTask */
				$toTask = $relation->getTask();

				$endDate = $toTask->getEndDatePlan();
				if(!$endDate) // somehow there are no end date, get out of here asap
				{
					continue;
				}

				$startDate = $relation->getMinDate();
				if (!$startDate)
				{
					continue;
				}
				$startDate = clone $startDate;

				$duration = $toTask->calculateDuration();

				$endDate = clone $startDate;
				$endDate->addSecond($duration);

				// set task dates here
				$toTask->setStartDatePlanUserTimeGmt($startDate->toStringGmt());
				$toTask->setEndDatePlanUserTimeGmt($endDate->toStringGmt());

				$toTask->correctWorkTime($startDate, $endDate, $duration);

				// update $relation lag
				$relation->updateLag();
				$relation->setProcessed();

				$this->updateRelatedTasks($toTaskId, $relations);
			}
		}
	}

	/**
	 * @param $id
	 * @param $tasks
	 * @param Result $result
	 * @return array
	 */
	private function checkCoherence($id, $tasks, $result)
	{
		$accessible = array();
		$queue = array($id);
		$limit = 0;
		$met = array();

		while(count($queue) && $limit < 10000)
		{
			$limit++;
			$nextId = array_shift($queue);
			if($met[$nextId])
			{
				//$result->addError('ILLEGAL_STRUCTURE.DEPTH', 'Oops, there is a structure loop ('.$nextId.' met twice)');
				//break;
				continue; // just do not go the same way twice
			}
			$met[$nextId] = true;

			if(is_array($tasks[$nextId]))
			{
				foreach($tasks[$nextId] as $nextSubId => $nextSub)
				{
					if($this->canModifyTask($nextSub))
					{
						$accessible[$nextSubId] = $nextSub;
						$queue[] = $nextSubId;
					}
				}
			}
		}

		if($limit == 10000)
		{
			$result->addError('ILLEGAL_STRUCTURE.DEPTH', 'Insane project tree depth faced');
		}

		return $accessible;
	}

	private function canModifyTask($task)
	{
		// no deal with tasks with no created date, skip the subtree
		if((string) $task['CREATED_DATE'] == '')
		{
			return false;
		}

		$instance = \CTaskItem::getInstance($task['ID'], $this->getUserId()); // todo: user should come from outside
		return $instance->checkAccess(ActionDictionary::ACTION_TASK_DEADLINE);
	}

	public function dumpRelations($relations)
	{
		if(!$this->isDebugEnabled())
		{
			return;
		}

		/** @var Relation $relation */
		foreach($relations as $relation)
		{
			_print_r($relation->dump());
		}
	}
}