<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 * 
 * @access private
 * 
 * This class is a partial port of javascript GanttDependency and GanttTask classes
 */

namespace Bitrix\Tasks\Util;

use \Bitrix\Main\Entity\ReferenceField;

use \Bitrix\Tasks\Task\DependenceTable;
use \Bitrix\Tasks\Util\Type\DateTime;
use \Bitrix\Tasks\Util\Calendar;
use \Bitrix\Tasks\TaskTable;
use \Bitrix\Tasks\Util\Assert;

final class Scheduler
{
	const DEFAULT_DURATION = 9;

	protected $relations = 	array();
	protected $taskPool = 	array();
	protected $changed = array();
	protected $calendar = 	null;
	protected $taskId = 	false;
	protected $processId = 	null;
	protected $userId = null;

	protected $taskOriginDates = array();

	public function __construct($userId, $task, array $taskData = array(), Calendar $calendar = null)
	{
		if($calendar === null)
		{
			$this->calendar = new Calendar();
		}
		else
		{
			$this->calendar = $calendar;
		}

		$this->userId = Assert::expectIntegerPositive($userId, '$userId', 'Incorrect userId passed');

		if(is_object($task) && $task instanceof \CTaskItem)
		{
			$this->taskId = $task->getId();
			$this->initializeTaskFromObject($task);
		}
		else
		{
			$this->taskId = intval($task);

			if(!$this->taskId && empty($taskData))
			{
				throw new ArgumentException('Neither correct task id nor task data specified');
			}

			$this->initializeTaskFromData($taskData);
		}
	}

	/**
	 * Initialize START_DATE_PLAN and END_DATE_PLAN if they were not initialized
	 * In gantt.js it called setRealDates()
	 */
	public function defineDates()
	{
		$task = $this->taskPool[$this->taskId];
		
		$startDatePlanSet = (string) $task['START_DATE_PLAN'] != '';
		$endDatePlanSet = 	(string) $task['END_DATE_PLAN'] != '';

		if($startDatePlanSet && $endDatePlanSet)
		{
			return;
		}

		// set end date as the end of the day
		$task->setEndDatePlanUserTimeGmt($this->calendar->getEndOfCurrentDayGmt()->toStringGmt());

		if($task->getMatchWorkTime())
		{
			$duration = $task->calculateDuration();
			$secInHour = 3600;
			if($duration < self::DEFAULT_DURATION * $secInHour)
			{
				$duration = self::DEFAULT_DURATION * $secInHour;
			}

			$startDate = $task->getStartDatePlanGmt(true);

			if(!$startDatePlanSet)
			{
				$task->setStartDatePlanUserTimeGmt($this->calendar->getClosestWorkTime($startDate, true)->toStringGmt());
			}
			if(!$endDatePlanSet)
			{
				$task->setEndDatePlanUserTimeGmt($this->calendar->calculateEndDate($startDate, $duration)->toStringGmt());
			}
		}

		$this->changed[$task->getId()] = true;
	}

	public function sync()
	{
		foreach($this->taskPool as $task)
		{
			if(isset($this->changed[$task->getId()]))
			{
				/*
				_dump_r('Update '.$task->getId().' :');
				_dump_r(array(
					'START_DATE_PLAN' => $task->getStartDatePlan(true)->toString(),
					'END_DATE_PLAN' => $task->getEndDatePlan()->toString(),
					'DURATION' => $task->calculateDuration()
				));
				*/

				$task->update(array(
					'START_DATE_PLAN' => $task->getStartDatePlan(true)->toString(),
					'END_DATE_PLAN' => $task->getEndDatePlan()->toString(),
					'DURATION' => $task->calculateDuration()
				), array(
					'CORRECT_DATE_PLAN_DEPENDENT_TASKS' => false,
					'CORRECT_DATE_PLAN' => false,
					'THROTTLE_MESSAGES' => true
				));

				unset($this->changed[$task->getId()]);
			}
		}
	}

	/**
	 * Reschedule everyting: the task itself and its related tasks
	 */
	public function reSchedule(array $newData)
	{
		$result = array(
			$this->reScheduleTask($newData)
		);
		$result = array_merge($result, $this->reScheduleRelatedTasks());

		return $result;
	}

	/**
	 * Reschedule only current task
	 */
	public function reScheduleTask(array $newData)
	{
		$task = $this->taskPool[$this->taskId];

		// do not deal with tasks with no created date
		if((string) $task['CREATED_DATE'] == '')
		{
			return array();
		}

		if(isset($newData['MATCH_WORK_TIME']))
		{
			$task->setMatchWorkTime($newData['MATCH_WORK_TIME'] == 'Y');
		}

		// define end date...
		if((string) $task['END_DATE_PLAN'] == '')
		{
			if((string) $newData['END_DATE_PLAN'] != '')
			{
				$task->setEndDatePlan(new DateTime($newData['END_DATE_PLAN']));
			}
			else
			{
				$task->setEndDatePlanUserTimeGmt($this->calendar->getEndOfCurrentDayGmt()->toStringGmt());
			}
		}

		// must keep previous values of task; we`ll need it on relcalculating related task`s lags further
		$this->taskOriginDates = array(
			'START_DATE_PLAN' => $task->getStartDatePlanGmt(true),
			'END_DATE_PLAN' => $task->getEndDatePlanGmt(),
		);

		// $startDate and $endDate are dates that were changed, not just actual dates!
		$startDate = null;
		$endDate = null;

		if($newData['MATCH_WORK_TIME'] == 'Y')
		{
			// MATCH_WORK_TIME was set to Y, we have to reschedule and re-check existing dates even if no actual date change required
			$startDate = 	isset($newData['START_DATE_PLAN']) ? $newData['START_DATE_PLAN'] : $task['START_DATE_PLAN'];
			$endDate = 		isset($newData['END_DATE_PLAN']) ? $newData['END_DATE_PLAN'] : $task['END_DATE_PLAN'];
		}
		else
		{
			// MATCH_WORK_TIME is set to N or even left unchanged, we just take what we have in $newData (if we have smth)
			if(isset($newData['START_DATE_PLAN']))
			{
				$startDate = $newData['START_DATE_PLAN'];
			}
			if(isset($newData['END_DATE_PLAN']))
			{
				$endDate = $newData['END_DATE_PLAN'];
			}
		}

		// we need ensure we deal with DateTime objects in $startDate and $endDate, not strings or smth else
		$startDate = 	$this->castToDateTimeGmt($startDate);
		$endDate = 		$this->castToDateTimeGmt($endDate);

		if($startDate || $endDate) // smth were changed, do job
		{
			/*
			// we had no end date, but we get it in $newData.
			if($endDate && !$task['END_DATE_PLAN'])
			{
				$task->setEndDatePlanUserTimeGmt($endDate->toStringGmt());
			}
			if(!$endDate && !$task['END_DATE_PLAN']) // no data to shift
			{
				return static::makeTaskReturnStruct($task);
			}
			*/



			if($startDate && $endDate) // both changed => shift
			{
				$task->setStartDatePlanUserTimeGmt($startDate->toStringGmt());
				$task->setEndDatePlanUserTimeGmt($endDate->toStringGmt());

				$duration = $task->calculateDuration();
				$this->matchWorkingTime($this->taskId, $startDate, $endDate, $duration);
			}
			elseif($startDate) // start changed => resize to left
			{
				$task->setStartDatePlanUserTimeGmt($startDate->toStringGmt());

				$this->matchWorkingTime($this->taskId, $startDate);
			}
			elseif($endDate) // end changed => resize to right
			{
				$task->setEndDatePlanUserTimeGmt($endDate->toStringGmt());

				$this->matchWorkingTime($this->taskId, null, $endDate);
			}
		}

		return static::makeTaskReturnStruct($task, $newData);
	}

	/**
	 * Reschedule related tasks of the current task
	 */
	public function reScheduleRelatedTasks()
	{
		// root task has no created date, exit
		if((string) $this->taskPool[$this->taskId]['CREATED_DATE'] == '')
		{
			return array();
		}

		$this->getTaskRelationHash();
		$this->calculateLags();

		$this->processId = time(); // "transaction" in js
		$this->updateRelated($this->taskId);

		$this->taskOriginDates = array(); // drop previous

		return $this->exportTaskPool();
	}

	protected static function makeTaskReturnStruct(\CTaskItem $task, $dataDelta = false)
	{
		$result = array(
			'ID' => 					$task['ID'],
			'START_DATE_PLAN' => 		$task->getStartDatePlan(true), // empty or non-empty (but unchanged), whatever
			'END_DATE_PLAN' => 			$task->getEndDatePlan(), // empty or non-empty (but unchanged), whatever
			'MATCH_WORK_TIME' => 		$task['MATCH_WORK_TIME'] == 'Y',
			'DURATION_PLAN_SECONDS' => 	$task->calculateDuration(),
		);

		if(is_array($dataDelta))
		{
			if((string) $dataDelta['START_DATE_PLAN'] == '') // will be dropped
			{
				unset($result['START_DATE_PLAN']);
			}
			if((string) $dataDelta['END_DATE_PLAN'] == '') // will be dropped
			{
				unset($result['END_DATE_PLAN']);
			}
		}

		return $result;
	}

	protected static function castToDateTimeGmt($date)
	{
		if($date == null)
		{
			return null;
		}

		if(is_string($date))
		{
			// $date is treated as LOCAL TIME, since we have no information about time zone
			return DateTime::createFromUserTimeGmt($date);
		}
		elseif($date instanceof \Bitrix\Main\Type\DateTime)
		{
			// to local time ...
			$date = $date->toString();

			// .. and return as gmt
			return DateTime::createFromUserTimeGmt($date);
		}
		else
		{
			throw new \Bitrix\Main\ArgumentException('Unsupported type of date');
		}
	}

	protected function initializeTaskFromObject(\CTaskItem $task)
	{
		$this->taskPool[$this->taskId] = $task;
		$this->taskPool[$this->taskId]->setCalendar($this->calendar);

		static::checkAccessThrowException($this->taskPool[$this->taskId]);
	}

	protected function initializeTaskFromData(array $taskData = array())
	{
		if(empty($taskData))
		{
			$select = array(

				'ID',
				'MATCH_WORK_TIME',
				'ALLOW_CHANGE_DEADLINE',
				//'DURATION_TYPE',

				'START_DATE_PLAN',
				'END_DATE_PLAN',
				'CREATED_DATE',

				'RESPONSIBLE_ID',
				'CREATED_BY',
				'GROUP_ID',
				'STATUS' => 'REAL_STATUS',

			);

			$taskData = TaskTable::getList(array(
				'filter' => array('=ID' => $this->taskId),
				'select' => $select
			))->fetch();
		}
		else
		{
			if(!isset($taskData['MATCH_WORK_TIME']))
			{
				$taskData['MATCH_WORK_TIME'] = 'N'; // assume no
			}
		}

		if(!isset($taskData['ID']))
		{
			if($this->taskId)
			{
				$taskData['ID'] = $this->taskId;
			}
			else
			{
				$taskData['ID'] = PHP_INT_MAX;
			}
		}

		$this->taskPool[$this->taskId] = \CTaskItem::constructWithPreloadedData($this->userId, $taskData);
		$this->taskPool[$this->taskId]->setCalendar($this->calendar);

		if($this->taskId > 0)
		{
			static::checkAccessThrowException($this->taskPool[$this->taskId]);
		}
	}

	private static function checkAccessThrowException(\CTaskItem $task)
	{
		if(!$task->isActionAllowed(\CTaskItem::ACTION_CHANGE_DEADLINE))
		{
			throw new \Bitrix\Tasks\ActionNotAllowedException(false, array(
				'AUX' => array(
					'MESSAGE' => array('TASK_ID' => $task->getId())
				)
			));
		}
	}

	protected function exportTaskPool()
	{
		$result = array();

		if(is_array($this->taskPool))
		{
			foreach($this->taskPool as $task)
			{
				if($task->getId() == $this->taskId)
				{
					continue;
				}

				// should be in "localtime" here (actually, its utc but without a timezone info)
				$result[$task->getId()] = static::makeTaskReturnStruct($task);
			}
		}

		return $result;
	}

	protected function updateRelated($fromTaskId)
	{
		if(is_array($this->relations[$fromTaskId]))
		{
			foreach($this->relations[$fromTaskId] as &$relation)
			{
				if($relation['PROCESSED_BY'] == $this->processId) // already been here
				{
					continue;
				}

				$toTaskId = 	$relation['TASK_ID'];
				$toTask = 		$this->taskPool[$toTaskId];

				$endDate = $toTask->getEndDatePlan();
				if(!$endDate) // somehow there are no end date, get out of here asap
				{
					continue;
				}

				$startDate = $this->getMinDate($relation);
				$startDate = clone $startDate;

				$duration = $toTask->calculateDuration();

				$endDate = clone $startDate;
				$endDate->addSecond($duration);

				// set task dates here
				$toTask->setStartDatePlanUserTimeGmt($startDate->toStringGmt());
				$toTask->setEndDatePlanUserTimeGmt($endDate->toStringGmt());

				$this->correctWorkTime($toTaskId, $startDate, $endDate, $duration);

				// update $relation lag
				$this->updateLag($relation);
				$relation['PROCESSED_BY'] = $this->processId;

				$this->updateRelated($toTaskId);
			}
			unset($relation);
		}
	}

	public static function convertDurationToUnits($duration = 0, $preferredUnits = \CTasks::TIME_UNIT_TYPE_HOUR)
	{
		$duration = intval($duration);
		$result = array(
			'TYPE' => $preferredUnits,
			'VALUE' => 0
		);

		if($duration)
		{
			// $duration comes in seconds
			// cast to minutes
			$mDuration = round($duration / 60, 0);

			if($preferredUnits == \CTasks::TIME_UNIT_TYPE_MINUTE)
			{
				$result['VALUE'] = $mDuration;
			}
			else
			{
				if($preferredUnits == \CTasks::TIME_UNIT_TYPE_HOUR)
				{
					$hDuration = $mDuration / 60;

					if(fmod($hDuration, 1) > 0)
					{
						$result['VALUE'] = $mDuration;
						$result['TYPE'] = \CTasks::TIME_UNIT_TYPE_MINUTE;
					}
					else
					{
						$result['VALUE'] = $hDuration;
					}
				}
				elseif($preferredUnits == \CTasks::TIME_UNIT_TYPE_DAY)
				{
					$dDuration = $mDuration / (24*60);

					if(fmod($dDuration, 1) > 0)
					{
						$hDuration = $mDuration / 60;

						if(fmod($hDuration, 1) > 0)
						{
							$result['VALUE'] = $mDuration;
							$result['TYPE'] = \CTasks::TIME_UNIT_TYPE_MINUTE;
						}
						else
						{
							$result['VALUE'] = $hDuration;
							$result['TYPE'] = \CTasks::TIME_UNIT_TYPE_HOUR;
						}
					}
					else
					{
						$result['VALUE'] = $dDuration;
					}
				}
			}
		}

		return $result;
	}

	protected function getMinDate(array $relation)
	{
		$toTaskId = 	$relation['TASK_ID'];
		$fromTaskID = 	$relation['FROM_TASK_ID'];

		$toTask = 		$this->taskPool[$toTaskId];
		$fromTask = 	$this->taskPool[$fromTaskID];
		$matchWorkTime = $this->taskPool[$toTaskId]['MATCH_WORK_TIME'] == 'Y';

		$startDate = null;
		$duration = $toTask->calculateDuration();

		if ($relation['TYPE'] == DependenceTable::LINK_TYPE_START_START)
		{
			$startDate = $fromTask->getStartDatePlanGmt(true);
		}
		else if ($relation['TYPE'] == DependenceTable::LINK_TYPE_START_FINISH)
		{
			if ($matchWorkTime)
			{
				$startDate = $this->calendar->calculateStartDate($fromTask->getStartDatePlanGmt(true), $duration);
			}
			else
			{
				$startDate = clone $fromTask->getStartDatePlanGmt(true);
				$startDate->addSecond(-$duration);
			}

		}
		else if ($relation['TYPE'] == DependenceTable::LINK_TYPE_FINISH_FINISH)
		{
			if ($matchWorkTime)
			{
				$startDate = $this->calendar->calculateStartDate($fromTask->getEndDatePlanGmt(), $duration);
			}
			else
			{
				$startDate = clone $fromTask->getEndDatePlanGmt();
				$startDate->addSecond(-$duration);
			}
		}
		else
		{
			$startDate = $fromTask->getEndDatePlanGmt();
		}

		$startDate = clone $startDate;

		if ($matchWorkTime)
		{
			return $relation['LAG'] > 0 ?
				$this->calendar->calculateEndDate($startDate, $relation['LAG']) :
				$this->calendar->calculateStartDate($startDate, abs($relation['LAG']));
		}
		else
		{
			$startDate->addSecond($relation['LAG']);
			return $startDate;
		}
	}

	protected function calculateLags()
	{
		if(is_array($this->relations))
		{
			foreach($this->relations as $parent => &$level)
			{
				if(is_array($level))
				{
					foreach($level as $id => &$rel)
					{
						$this->updateLag($rel);
					}
					unset($rel);
				}
			}
			unset($level);
		}
	}

	protected function updateLag(&$relation)
	{
		if($relation['FROM_TASK_ID'] == $this->taskId && !empty($this->taskOriginDates))
		{
			$fromTaskDateStart = 	$this->taskOriginDates['START_DATE_PLAN'];
			$fromTaskDateEnd = 		$this->taskOriginDates['END_DATE_PLAN'];
		}
		else
		{
			$fromTaskDateStart = 	$this->taskPool[$relation['FROM_TASK_ID']]->getStartDatePlanGmt(true);
			$fromTaskDateEnd = 		$this->taskPool[$relation['FROM_TASK_ID']]->getEndDatePlanGmt();
		}

		$toTaskDateStart = 		$this->taskPool[$relation['TASK_ID']]->getStartDatePlanGmt(true);
		$toTaskDateEnd = 		$this->taskPool[$relation['TASK_ID']]->getEndDatePlanGmt();

		/*
		print_r($relation['FROM_TASK_ID'].' => '.$relation['TASK_ID'].PHP_EOL);
		print_r('From task date start '.$fromTaskDateStart->toStringGmt().' '.$fromTaskDateStart->getTimeStamp().PHP_EOL);
		print_r('From task date end '.$fromTaskDateEnd->toStringGmt().' '.$fromTaskDateEnd->getTimeStamp().PHP_EOL);
		print_r('To task date start '.$toTaskDateStart->toStringGmt().' '.$toTaskDateStart->getTimeStamp().PHP_EOL);
		print_r('To task date end '.$toTaskDateEnd->toStringGmt().' '.$toTaskDateEnd->getTimeStamp().PHP_EOL);
		*/

		$matchWorkTime = $this->taskPool[$relation['TASK_ID']]['MATCH_WORK_TIME'] == 'Y';

		if ($relation['TYPE'] == DependenceTable::LINK_TYPE_START_START)
		{
			if ($matchWorkTime)
			{
				$lag = $this->calendar->calculateDuration($fromTaskDateStart, $toTaskDateStart);
			}
			else
			{
				$lag = $toTaskDateStart->getTimestamp() - $fromTaskDateStart->getTimestamp();
			}
		}
		else if ($relation['TYPE'] == DependenceTable::LINK_TYPE_START_FINISH)
		{
			if ($matchWorkTime)
			{
				$lag = $this->calendar->calculateDuration($fromTaskDateStart, $toTaskDateEnd);
			}
			else
			{
				$lag = $toTaskDateEnd->getTimestamp() - $fromTaskDateStart->getTimestamp();
			}
		}
		else if ($relation['TYPE'] == DependenceTable::LINK_TYPE_FINISH_FINISH)
		{
			if ($matchWorkTime)
			{
				$lag = $this->calendar->calculateDuration($fromTaskDateEnd, $toTaskDateEnd);
			}
			else
			{
				$lag = $toTaskDateEnd->getTimestamp() - $fromTaskDateEnd->getTimestamp();
			}
		}
		else
		{
			if ($matchWorkTime)
			{
				$lag = $this->calendar->calculateDuration($fromTaskDateEnd, $toTaskDateStart);
			}
			else
			{
				$lag = $toTaskDateStart->getTimestamp() - $fromTaskDateEnd->getTimestamp();
			}
		}

		$relation['LAG'] = $lag;
	}

	protected function getTaskRelationHash()
	{
		$id = $this->taskId;
		$result = array();

		if($id)
		{
			global $DB;

			$sql = DependenceTable::getSubTreeSql($id);
			$res = $DB->query($sql);

			$items = array(); // all items
			$taskData = array($id => true); // actual task list in bundle
			while($item = $res->fetch())
			{
				// make datetime objects
				if($item['CREATED_DATE'])
				{
					$item['CREATED_DATE'] = DateTime::createFromUserTimeGmt($item['CREATED_DATE']);
				}
				if($item['START_DATE_PLAN'])
				{
					$item['START_DATE_PLAN'] = DateTime::createFromUserTimeGmt($item['START_DATE_PLAN']);
				}
				if($item['END_DATE_PLAN'])
				{
					$item['END_DATE_PLAN'] = DateTime::createFromUserTimeGmt($item['END_DATE_PLAN']);
				}

				$items[] = $item;

				$taskId = $item['TASK_ID'];
				unset($item['TASK_ID']);
				unset($item['TYPE']);
				unset($item['FROM_TASK_ID']);

				$taskData[$taskId] = $item;
			}

			$relations = array();
			foreach($items as $item)
			{
				if(isset($taskData[$item['FROM_TASK_ID']])) // due to multiple-parent relations there are extraneous links possibe (came from join)
				{
					$pid = $item['FROM_TASK_ID'];

					$relations[$pid][$item['TASK_ID']] = array(
						'TASK_ID' => $item['TASK_ID'],
						'TYPE' => $item['TYPE'],
						'FROM_TASK_ID' => $item['FROM_TASK_ID'],
					);
				} // else skip this relation
			}

			$this->makeRelationTree($this->taskId, $relations, $taskData);

			$filteredRelations = array();
		}
	}

	private function makeRelationTree($fromTaskId, array $relations, array $taskData)
	{
		if(is_array($relations[$fromTaskId]) && !empty($relations[$fromTaskId]))
		{
			foreach($relations[$fromTaskId] as $taskId => $relation)
			{
				if(!isset($this->taskPool[$taskId]))
				{
					$taskItem = \CTaskItem::constructWithPreloadedData($this->userId, $taskData[$taskId]);
					$taskItem->setCalendar($this->calendar);
				}
				else
				{
					$taskItem = $this->taskPool[$taskId];
				}

				// no deal with tasks with no created date, skip the subtree
				if((string) $taskItem['CREATED_DATE'] == '')
				{
					continue;
				}

				if(!$taskItem->isActionAllowed(\CTaskItem::ACTION_CHANGE_DEADLINE)) // no access, skip the whole subtree
				{
					continue;
				}
				else
				{
					$this->taskPool[$taskId] = $taskItem;
					$this->relations[$fromTaskId][$taskId] = $relation;

					$this->makeRelationTree($taskId, $relations, $taskData);
				}
			}
		}
	}

	// GanttTask

	protected function correctWorkTime($taskId, DateTime $startDate, Datetime $endDate, $duration)
	{
		/*
		print_r('##############################################'.PHP_EOL);
		print_r('Correcting '.$taskId.' with duration '.$duration.PHP_EOL);

		print_r('Trying to update '.$taskId.' to:'.PHP_EOL);
		print_r($startDate->getInfoGmt().' (was '.$this->taskPool[$taskId]->getStartDatePlanGmt(true)->getInfoGmt().')'.PHP_EOL);
		print_r($endDate->getInfoGmt().' (was '.$this->taskPool[$taskId]->getEndDatePlanGmt()->getInfoGmt().')'.PHP_EOL);
		*/

		$task = $this->taskPool[$taskId];

		if ($this->taskPool[$taskId]['MATCH_WORK_TIME'] != 'Y')
		{
			/*
			print_r('Actual moving '.$taskId.' to:'.PHP_EOL);
			print_r($task['START_DATE_PLAN']->getInfoGmt().PHP_EOL);
			print_r($task['END_DATE_PLAN']->getInfoGmt().PHP_EOL);
			*/

			return; // do nothing, dates are okay already
		}
		else
		{
			if (!$this->calendar->isWorkTime($startDate))
			{
				$task->setStartDatePlanUserTimeGmt($this->calendar->getClosestWorkTime($startDate, true)->toStringGmt());
				$task->setEndDatePlanUserTimeGmt($this->calendar->calculateEndDate($startDate, $duration)->toStringGmt());
			}
			else
			{
				$task->setEndDatePlanUserTimeGmt($this->calendar->calculateEndDate($startDate, $duration)->toStringGmt());
			}
		}

		/*
		print_r('Actual moving '.$taskId.' to:'.PHP_EOL);
		print_r($task['START_DATE_PLAN']->getInfoGmt().PHP_EOL);
		print_r($task['END_DATE_PLAN']->getInfoGmt().PHP_EOL);
		*/
	}

	protected function matchWorkingTime($taskId, DateTime $startDate = null, Datetime $endDate = null, $duration = null)
	{
		$task = $this->taskPool[$taskId];

		if ($this->taskPool[$taskId]['MATCH_WORK_TIME'] != 'Y')
		{
			/*
			print_r('Actual moving '.$taskId.' to:'.PHP_EOL);
			print_r($task['START_DATE_PLAN']->getInfoGmt().PHP_EOL);
			print_r($task['END_DATE_PLAN']->getInfoGmt().PHP_EOL);
			*/

			return; // do nothing, dates are okay already
		}
		else
		{
			if ($startDate && $endDate)
			{
				$this->correctWorkTime($taskId, $startDate, $endDate, $duration);
			}
			elseif($startDate)
			{
				$task->setStartDatePlanUserTimeGmt($this->calendar->getClosestWorkTime($startDate, true)->toStringGmt());

				$start = $task->getStartDatePlanGmt(true);
				$end = $task->getEndDatePlanGmt();

				if ($start->checkGT($end))
				{
					$task->setEndDatePlanUserTimeGmt($this->calendar->calculateEndDate($startDate, $end->getTimestamp() - $startDate->getTimestamp())->toStringGmt());
				}
			}
			else if ($endDate)
			{
				$task->setEndDatePlanUserTimeGmt($this->calendar->getClosestWorkTime($endDate, false)->toStringGmt());

				$start = $task->getStartDatePlanGmt(true);
				$end = $task->getEndDatePlanGmt();

				if ($start->checkGT($end))
				{
					$task->setStartDatePlanUserTimeGmt($this->calendar->calculateStartDate($end, $endDate->getTimestamp() - $start->getTimestamp())->toStringGmt());
				}
			}
		}
	}
}