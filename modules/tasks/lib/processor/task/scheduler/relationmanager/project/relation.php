<?
/**
 * This class could be changed (removed, renamed, relocated) in any time, so do not use it in public code
 *
 * @internal
 * @access private
 */

namespace Bitrix\Tasks\Processor\Task\Scheduler\RelationManager\Project;

use Bitrix\Tasks\Internals\Task\ProjectDependenceTable;
use Bitrix\Tasks\Util\Calendar;
use Bitrix\Tasks\Util\Type\DateTime;

final class Relation extends \Bitrix\Tasks\Processor\Task\Scheduler\Relation
{
	protected $type = 0;
	protected $lag = 0;
	protected $processed = false;

	public function __construct(array $data)
	{
		$this->type = $data['TYPE'];
	}

	public function getType()
	{
		return $this->type;
	}

	public function getLag()
	{
		return $this->lag;
	}

	public function setTask($task)
	{
		parent::setTask($task);
		$this->updateLag();
	}

	public function setParentTask($task)
	{
		parent::setParentTask($task);
		$this->updateLag();
	}

	public function setProcessed()
	{
		$this->processed = true;
	}

	public function isProcessed()
	{
		return $this->processed;
	}

	/**
	 * @return DateTime
	 */
	public function getMinDate()
	{
		$toTask = $this->getTask();
		$fromTask = $this->getParentTask();
		$matchWorkTime = $toTask->getMatchWorkTime();

		$startDate = null;
		$endDateGmt = $fromTask->getEndDatePlanGmt();
		$duration = $toTask->calculateDuration();

		$calender = Calendar::getInstance();

		if($this->getType() == ProjectDependenceTable::LINK_TYPE_START_START)
		{
			$startDate = $fromTask->getStartDatePlanGmt(true);
		}
		elseif($this->getType() == ProjectDependenceTable::LINK_TYPE_START_FINISH)
		{
			if($matchWorkTime)
			{
				$startDate = $calender->calculateStartDate($fromTask->getStartDatePlanGmt(true), $duration);
			}
			else
			{
				$startDate = clone $fromTask->getStartDatePlanGmt(true);
				$startDate->addSecond(-$duration);
			}

		}
		elseif($this->getType() == ProjectDependenceTable::LINK_TYPE_FINISH_FINISH)
		{
			if ($endDateGmt !== null)
			{
				if($matchWorkTime)
				{
					$startDate = $calender->calculateStartDate($endDateGmt, $duration);
				}
				else
				{
					$startDate = clone $endDateGmt;
					$startDate->addSecond(-$duration);
				}
			}
		}
		else
		{
			$startDate = $endDateGmt;
		}
		//_print_r('SSStart date: '.\Bitrix\Tasks\Processor\Task\Scheduler\Impact::dateTimeGmtToLocalString($startDate));

		if ($startDate == null)
		{
			return null;
		}

		$startDate = clone $startDate;

		if ($matchWorkTime)
		{
			return $this->getLag() > 0 ?
				$calender->calculateEndDate($startDate, $this->getLag()) :
				$calender->calculateStartDate($startDate, abs($this->getLag()));
		}
		else
		{
			$startDate->addSecond($this->getLag());
			return $startDate;
		}
	}

	public function updateLag()
	{
		if(!$this->getTask() || !$this->getParentTask())
		{
			return;
		}

		$parentTask = $this->getParentTask();
		$parentPristineData = $parentTask->getDataPristine();
		$task = $this->getTask();
		$calendar = Calendar::getInstance();

		// head task was already changed outside this worker, but we need to get original lag, so use pristine dates
		if($parentTask->isHead() && !empty($parentPristineData))
		{
			$fromTaskDateStart = $parentPristineData['START_DATE_PLAN_GMT'];
			$fromTaskDateEnd = $parentPristineData['END_DATE_PLAN_GMT'];
		}
		else
		{
			$fromTaskDateStart = $parentTask->getStartDatePlanGmt(true);
			$fromTaskDateEnd = $parentTask->getEndDatePlanGmt();
		}

		$toTaskDateStart = $task->getStartDatePlanGmt(true);
		$toTaskDateEnd = $task->getEndDatePlanGmt();

		/*
		print_r($relation['FROM_TASK_ID'].' => '.$relation['TASK_ID'].PHP_EOL);
		print_r('From task date start '.$fromTaskDateStart->toStringGmt().' '.$fromTaskDateStart->getTimeStamp().PHP_EOL);
		print_r('From task date end '.$fromTaskDateEnd->toStringGmt().' '.$fromTaskDateEnd->getTimeStamp().PHP_EOL);
		print_r('To task date start '.$toTaskDateStart->toStringGmt().' '.$toTaskDateStart->getTimeStamp().PHP_EOL);
		print_r('To task date end '.$toTaskDateEnd->toStringGmt().' '.$toTaskDateEnd->getTimeStamp().PHP_EOL);
		*/

		$matchWorkTime = $task->getMatchWorkTime();

		if ($this->getType() == ProjectDependenceTable::LINK_TYPE_START_START)
		{
			if ($matchWorkTime)
			{
				$lag = $calendar->calculateDuration($fromTaskDateStart, $toTaskDateStart);
			}
			else
			{
				$lag = $toTaskDateStart->getTimestamp() - $fromTaskDateStart->getTimestamp();
			}
		}
		else if ($this->getType() == ProjectDependenceTable::LINK_TYPE_START_FINISH)
		{
			if ($matchWorkTime)
			{
				$lag = $calendar->calculateDuration($fromTaskDateStart, $toTaskDateEnd);
			}
			else
			{
				$lag = $toTaskDateEnd->getTimestamp() - $fromTaskDateStart->getTimestamp();
			}
		}
		else if ($this->getType() == ProjectDependenceTable::LINK_TYPE_FINISH_FINISH)
		{
			if ($matchWorkTime)
			{
				$lag = $calendar->calculateDuration($fromTaskDateEnd, $toTaskDateEnd);
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
				$lag = $calendar->calculateDuration($fromTaskDateEnd, $toTaskDateStart);
			}
			else
			{
				$lag = $toTaskDateStart->getTimestamp() - $fromTaskDateEnd->getTimestamp();
			}
		}

		$this->lag = $lag;
	}

	public function dump()
	{
		return
			'* RELATION:'.PHP_EOL.
			"\t\t".$this->getParentTask()->dump().PHP_EOL.
			"\t\t".'LAG: '.\Bitrix\Tasks\UI::formatTimeAmount($this->getLag()).PHP_EOL.
			"\t\t".$this->getTask()->dump();
	}
}