<?
/**
 * This class could be changed (removed, renamed, relocated) in any time, so do not use it in public code
 *
 * @internal
 * @access private
 */

namespace Bitrix\Tasks\Processor\Task\Scheduler\RelationManager;

use Bitrix\Tasks\Internals\DataBase\Structure\ClosureTree\Fragment;
use Bitrix\Tasks\Internals\Helper\Task\Dependence;
use Bitrix\Tasks\Internals\RunTime;
use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\Tasks\Processor\Task\Scheduler\Result\Impact;
use Bitrix\Tasks\Processor\Task\Result;

final class SubTask extends \Bitrix\Tasks\Processor\Task\Scheduler\RelationManager
{
	public static function getCode()
	{
		return 'S';
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

		$id = $rootImpact->getId();
		$mode = $settings['MODE'];

		//_print_r('============== Process subtask with '.$id);

		// get tree to recalculate
		$parentTreeFragment = null;

		if($mode == 'BEFORE_ATTACH')
		{
			$newParentId = $rootImpact->getParentId();
			if($newParentId)
			{
				$parentTreeFragment = $this->getParentTree($newParentId);
				if($parentTreeFragment) // we have smth to recalculate
				{
					if($id)
					{
						$subTreeFragment = $this->getSubTree($id);
						$subTreeFragment->setParentFor($id, $newParentId);

						$parentTreeFragment->includeFragment($subTreeFragment);
					}
					else
					{
						// and add one "virtual" node to our tree fragment, and then do calculations as usual
						$parentTreeFragment->addNode(-1, $newParentId, array(
							'START_DATE_PLAN' => $rootImpact->getStartDatePlan(true),
							'END_DATE_PLAN' => $rootImpact->getEndDatePlan(),
							'PARENT_ID' => $newParentId,

							'MATCH_WORK_TIME' => 'N', // whatever, Y or N, does not make any sense here: task is already processed
							'INHERIT_DATES' => 'N', // whatever, Y or N, does not make any sense here: task is already processed
						));
					}
				}
			}
		}
		elseif($mode == 'BEFORE_DETACH')
		{
			$oldParentId = $rootImpact->getParentId();
			if($oldParentId)
			{
				$parentTreeFragment = $this->getParentTree($oldParentId);
				if($parentTreeFragment) // we have smth to recalculate
				{
					// remove subtree with root at $id, not to take it into account
					$parentTreeFragment->excludeSubTreeFragment($id);
				}
			}
		}
		else
		{
			if($id)
			{
				// if task already created (typically, we are called from inside update()), get its parent tree
				$parentTreeFragment = $this->getParentTree($id);
			}
		}

		if($parentTreeFragment)
		{
			$current = $parentTreeFragment->getNodeData($id ? $id : -1);
			if($current)
			{
				// MODIFY SUBTREE: if it is a "bracket" task, it will shift its subtree
				if($current['INHERIT_DATES'] == 'Y')
				{
					$this->shiftSubTree($rootImpact, $parentTreeFragment);
				}
			}

			// MODIFY PATH: for each task we need to calculate min and max dates of its sub-tasks
			$this->resizeBrackets($parentTreeFragment);
		}
	}

	/**
	 * @param int $parentId
	 * @param int $id
	 * @param mixed[] $itemData
	 * @param mixed[] $miniMaxes
	 * @param boolean $isMin
	 *
	 * @return DateTime|null
	 */
	public function updateDateBoundary($parentId, $id, $itemData, $miniMaxes, $isMin)
	{
		return $this->internalUpdateDateBoundary($parentId, $id, $itemData, $miniMaxes, $isMin);
	}

	private function internalUpdateDateBoundary($parentId, $id, $itemData, $miniMaxes, $isMin)
	{
		$impactData = $this->getScheduler()->getImpactById($id);
		if($impactData)
		{
			// this task already has impact, so we need to take the freshest version
			$itemData = $impactData;
		}

		$key = $isMin ? 'MIN' : 'MAX';

		// get current value for $parentId
		/** @var DateTime $haveValue */
		$haveValue = $miniMaxes[$parentId][$key];

		//$haveValueString = (string) $haveValue;

		// get current value for $id
		/** @var DateTime $newValue */
		$newValue = $miniMaxes[$id][$key];
		if($newValue === null) // not in min\max table, get its own dates
		{
			$newValue = $itemData[$isMin ? 'START_DATE_PLAN' : 'END_DATE_PLAN'];
		}

		if(!is_object($newValue))
		{
			return null;
		}

		if($haveValue === null)
		{
			return clone $newValue;
		}

		if($isMin)
		{
			// looking for min
			if($haveValue->getTimestamp() > $newValue->getTimestamp())
			{
				return clone $newValue;
			}
		}
		else
		{
			// looking for max
			if($haveValue->getTimestamp() < $newValue->getTimestamp())
			{
				return clone $newValue;
			}
		}

		return $haveValue;
	}

	public static function isTaskBelong($id, $data = array())
	{
		// either task exists and linked, or it will be linked after save!
		return ($id && Dependence::isNodeExist($id)) || intval($data['PARENT_ID']);
	}

	private function shiftSubTree(Impact $rootImpact, Fragment $globalTree)
	{
		// if this is a "bracket" task, it can be shifted only
		// therefore, get delta between start dates, and shift $id`s subtree for this amount

		$id = $rootImpact->getId();
		$scheduler = $this->getScheduler();

		$deltaStart = $rootImpact->getStartDateDelta();
		$deltaEnd = $rootImpact->getStartDateDelta();

		if(is_infinite($deltaStart) || is_infinite($deltaEnd))
		{
			// oops, it is an endless task (or half-endless), retreat!
			return;
		}

		if($deltaStart)
		{
			// pick sub tree from the complete tree
			$subTree = $globalTree->getSubTree($id);

			if($subTree->isCorrect() && $subTree->count())
			{
				$subTreeData = $subTree->toArray();

				foreach($subTreeData as $subNode)
				{
					$subNode['ID'] = $subNode['__ID']; // todo: getId() of node item here

					if($subNode['ID'] == $id)
					{
						continue; // already shifted outside this manager
					}
					if(!$this->isTaskFinite($subNode))
					{
						continue;
					}

					$impact = new Impact($subNode, $this->getUserId());

					$impact->shiftDates($deltaStart);

					$scheduler->addImpact($impact);
					$scheduler->pushQueue($impact->getId(), $scheduler->getRelationProcessor('P'));
				}
			}
		}
	}

	/**
	 * @param $id
	 * @param Fragment $fragment
	 * @return array
	 */
	public function getInitialBoundaries($id, $fragment)
	{
		return $this->internalGetInitialBoundaries($id, $fragment);
	}

	private function internalGetInitialBoundaries($id, $fragment)
	{
		$min = null;
		$max = null;

		$impactData = $this->getScheduler()->getImpactById($id);
		$nodeData = $fragment->getNodeData($id);

		if(!static::isBracketTaskData($impactData) && !static::isBracketTaskData($nodeData))
		{
			$data = $nodeData;
			if($impactData)
			{
				$data = $impactData;
			}

			$min = $data['START_DATE_PLAN'] ? clone $data['START_DATE_PLAN'] : null;
			$max = $data['END_DATE_PLAN'] ? clone $data['END_DATE_PLAN'] : null;
		}

		return array(
			'MAX' => $max,
			'MIN' => $min
		);
	}

	private static function isBracketTaskData($data)
	{
		if(!is_array($data) && !is_object($data))
		{
			return false;
		}

		if($data['INHERIT_DATES'] == 'Y')
		{
			return true;
		}

		// todo: better to use objects here
		if(is_array($data['SE_PARAMETER']))
		{
			foreach($data['SE_PARAMETER'] as $param)
			{
				if($param['CODE'] == 1) // todo: use constant here, not just 1
				{
					return $param['VALUE'] == 'Y';
				}
			}
		}

		return false;
	}

	private function resizeBrackets(Fragment $fragment)
	{
		$scheduler = $this->getScheduler();

		$debug = false;

		$miniMaxes = array();
		$self = &$this; // [BUGS] 0083454
		$walkResult = $fragment->walkDepth(function($item, $id, $itemData, $parentId) use (&$miniMaxes, $scheduler, $fragment, $debug, $self) {

			$debug && _print_r('After ID = '.$id);

			if(!isset($miniMaxes[$parentId]))
			{
				// get from fragment or impact
				$miniMaxes[$parentId] = $self->getInitialBoundaries($parentId, $fragment);
				$debug && _print_r('---------- initialized '.$parentId.' '.((string) $miniMaxes[$parentId]['MIN']).' : '.((string) $miniMaxes[$parentId]['MAX']).')');
			}

			//$haveValueString = (string) $miniMaxes[16]['MIN'];

			// task is finite, process it
			$newBoundary = $self->updateDateBoundary(
				$parentId,
				$id,
				$itemData,
				$miniMaxes,
				true
			);
			if($newBoundary !== null)
			{
				//_print_r('Set '.$parentId.' MIN: '.((string) $newBoundary).' by '.$id);
				$miniMaxes[$parentId]['MIN'] = $newBoundary;
			}

			$newBoundary = $self->updateDateBoundary(
				$parentId,
				$id,
				$itemData,
				$miniMaxes,
				false
			);
			if($newBoundary !== null)
			{
				$miniMaxes[$parentId]['MAX'] = $newBoundary;
			}

			if($debug)
			{
				foreach($miniMaxes as $pid => $limits)
				{
					_print_r($pid.' >>> (min: '.$miniMaxes[$pid]['MIN'].', max: '.$miniMaxes[$pid]['MAX'].')');
				}
			}
		});
		if(!$walkResult->isSuccess())
		{
			return;
		}

		foreach($miniMaxes as $taskId => $dates)
		{
			//_print_r($taskId.' >>> (min: '.((string) $dates['MIN']).', max: '.((string) $dates['MAX']).')');

			if(!$taskId || !is_object($dates['MIN']) || !is_object($dates['MAX']))
			{
				continue;
			}

			/** @var DateTime[]|mixed[] $taskData */
			$taskData = $fragment->getNodeData($taskId);
			if($taskData['INHERIT_DATES'] == 'Y') // resize only "bracket" tasks
			{
				$impact = $scheduler->getImpactById($taskId);
				if($impact)
				{
					// this task was already moved, so move it again to the new position
					if ($impact['START_DATE_PLAN'] == null ||
						$impact['END_DATE_PLAN'] == null ||
						$impact['START_DATE_PLAN']->isNotEqualTo($dates['MIN']) ||
						$impact['END_DATE_PLAN']->isNotEqualTo($dates['MAX']))
					{
						$impact->setDataUpdated(array(
							'START_DATE_PLAN' => clone $dates['MIN'],
							'END_DATE_PLAN' => clone $dates['MAX'],
						));
					}
				}
				else
				{
					if ($taskData['START_DATE_PLAN'] == null ||
						$taskData['END_DATE_PLAN'] == null ||
						$taskData['START_DATE_PLAN']->isNotEqualTo($dates['MIN']) ||
						$taskData['END_DATE_PLAN']->isNotEqualTo($dates['MAX']))
					{
						$taskData['ID'] = $taskId;
						$impact = new Impact($taskData, $this->getUserId());

						$impact->setDataUpdated(array(
							'START_DATE_PLAN' =>clone $dates['MIN'],
							'END_DATE_PLAN' => clone $dates['MAX'],
						));

						$scheduler->addImpact($impact);
						$scheduler->pushQueue($impact->getId(), $scheduler->getRelationProcessor('P'));
					}
				}
			}
		}
	}

	private function isTaskFinite($data)
	{
		return is_object($data['START_DATE_PLAN']) && is_object($data['END_DATE_PLAN']);
	}

	private function getParentTree($id)
	{
		return Dependence::getParentTree($id, $this->getTreeParameters());
	}

	private function getSubTree($id)
	{
		return Dependence::getSubTree($id, $this->getTreeParameters());
	}

	private function getTreeParameters()
	{
		return Runtime::apply(array(
			'select' => array(
				// todo: use Impact::getBaseMixin() here
				//'TITLE' => 'TASK.TITLE', // tmp
				'MATCH_WORK_TIME' => 'TASK.MATCH_WORK_TIME',
				'START_DATE_PLAN' => 'TASK.START_DATE_PLAN',
				'END_DATE_PLAN' => 'TASK.END_DATE_PLAN',
				'PARENT_ID' => 'TASK.PARENT_ID',
				'INHERIT_DATES' => 'PARAMETER.VALUE',
			),
			'group' => array(
				'INHERIT_DATES', // ensure we will not get duplicates in the result
			)
		), array(
			RunTime\Task::getTask(array('REF_FIELD' => 'TASK_ID', 'JOIN_TYPE' => 'inner')),
			RunTime\Task\Parameter::getParameter(array('REF_FIELD' => 'TASK_ID', 'CODE' => 1)),
		));
	}
}