<?
/**
 * todo: Impact class is TEMPORAL, it should be replaced with (or at least inherited from) \Bitrix\Tasks\Item\Task when ready
 * This class could be changed (removed, renamed, relocated) in any time, so do not use it in public code
 *
 * @internal
 * @access private
 */

namespace Bitrix\Tasks\Processor\Task\Scheduler\Result;

use Bitrix\Main\Localization\Loc;

use Bitrix\Tasks\Util\Calendar;
use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\Result;
use Bitrix\Tasks\Util\User;

final class Impact extends \Bitrix\Tasks\Processor\Task\Result\Impact
{
	private $startDatePlanGmt = null;
	private $endDatePlanGmt = null;

	protected function getDefaultDuration()
	{
		return 9;
	}

	public function resetDates()
	{
		$this->getDataPristine();

		$startDatePlanSet = (string) $this['START_DATE_PLAN'] != '';
		$endDatePlanSet = (string) $this['END_DATE_PLAN'] != '';

		$calendar = Calendar::getInstance();

		// set end date as the end of the day
		$this->setEndDatePlanUserTimeGmt($calendar->getEndOfCurrentDayGmt()->toStringGmt());

		if($this->getMatchWorkTime())
		{
			$duration = $this->calculateDuration();
			$secInHour = 3600;
			if($duration < $this->getDefaultDuration() * $secInHour)
			{
				$duration = $this->getDefaultDuration() * $secInHour;
			}

			$startDate = $this->getStartDatePlanGmt(true);

			if(!$startDatePlanSet)
			{
				$this->setStartDatePlanUserTimeGmt($calendar->getClosestWorkTime($startDate, true)->toStringGmt());
			}
			if(!$endDatePlanSet)
			{
				$this->setEndDatePlanUserTimeGmt($calendar->calculateEndDate($startDate, $duration)->toStringGmt());
			}
		}
	}

	public function getDataPristine()
	{
		if (!count($this->dataPristine))
		{
			// must keep previous values of task; we`ll need it on recalculating related task`s lags further
			$this->dataPristine = array(
				'START_DATE_PLAN_GMT' => $this->getStartDatePlanGmt(true),
				'END_DATE_PLAN_GMT' => $this->getEndDatePlanGmt(),
				'START_DATE_PLAN' => $this->getStartDatePlan(true),
				'END_DATE_PLAN' => $this->getEndDatePlan(),
				'DURATION_PLAN_SECONDS' => $this->calculateDuration(),
			);
		}

		return $this->dataPristine;
	}

	public function getParentId()
	{
		return intval($this->data['PARENT_ID']);
	}

	/**
	 * todo: when inherited from \Bitrix\Tasks\Item\Task, create method reSchedule() there, to be able to use
	 * todo: \Bitrix\Tasks\Item\Task instance with dates recalculation according to the MATCH_WORK_TIME flag
	 *
	 * @param array $data
	 */
	public function setDataUpdated(array $data)
	{
		$this->getDataPristine();

		if(array_key_exists('PARENT_ID', $data))
		{
			$this->data['PARENT_ID'] = $data['PARENT_ID'];
		}

		if(array_key_exists('SE_PARAMETER', $data))
		{
			$this->data['SE_PARAMETER'] = $data['SE_PARAMETER'];
		}

		if(array_key_exists('START_DATE_PLAN', $data) || array_key_exists('END_DATE_PLAN', $data) || array_key_exists('MATCH_WORK_TIME', $data))
		{
			// $startDate and $endDate are dates that were changed, not just actual dates!
			$startDate = null;
			$endDate = null;

			if($data['MATCH_WORK_TIME'] == 'Y')
			{
				// MATCH_WORK_TIME was set to Y, we have to reschedule and re-check existing dates even if no actual date change required
				$startDate = isset($data['START_DATE_PLAN']) ? $data['START_DATE_PLAN'] : $this->data['START_DATE_PLAN'];
				$endDate = isset($data['END_DATE_PLAN']) ? $data['END_DATE_PLAN'] : $this->data['END_DATE_PLAN'];
			}
			else
			{
				// MATCH_WORK_TIME is set to N or even left unchanged, we just take what we have in $newData (if we have smth)
				if(isset($data['START_DATE_PLAN']))
				{
					$startDate = $data['START_DATE_PLAN'];
				}
				if(isset($data['END_DATE_PLAN']))
				{
					$endDate = $data['END_DATE_PLAN'];
				}
			}

			// we need ensure we deal with DateTime objects in $startDate and $endDate, not strings or smth else
			$startDate = $this->castToDateTimeGmt($startDate);
			$endDate = $this->castToDateTimeGmt($endDate);

			$isStartDateNull = $startDate == null;
			$isEndDateNull = $endDate == null;

			$this->setStartDatePlanUserTimeGmt(($isStartDateNull? '' : $startDate->toStringGmt()));
			$this->setEndDatePlanUserTimeGmt(($isEndDateNull? '' : $endDate->toStringGmt()));

			$duration = (($isStartDateNull || $isEndDateNull)? 0 : $this->calculateDuration());
			$this->data['DURATION_PLAN_SECONDS'] = $duration;
			$this->matchWorkingTime($startDate, $endDate, $duration);
		}
	}

	public function save()
	{
		$result = new Result();

		try
		{
			$prevUserId = User::getOccurAsId();
			User::setOccurAsId($this->userId);

			// todo: get rid of CTaskItem, use \Bitrix\Tasks\Item\Task when ready
			$t = new \CTaskItem($this->getId(), User::getAdminId());
			$t->update($this->getUpdatedData(), array(
				'THROTTLE_MESSAGES' => true,
				'CORRECT_DATE_PLAN' => false,
				'CORRECT_DATE_PLAN_DEPENDENT_TASKS' => false,
			));

			if($prevUserId)
			{
				User::setOccurAsId($prevUserId);
			}
		}
		catch(\TasksException $e)
		{
			$result->addException($e, Loc::getMessage('TASKS_WORKER_TASK_IMPACT_SAVE_ERROR'));
		}
		catch(\CTaskAssertException $e)
		{
			$result->addException($e, Loc::getMessage('TASKS_WORKER_TASK_IMPACT_SAVE_ERROR'));
		}

		return $result;
	}

	public function correctWorkTime(DateTime $startDate, Datetime $endDate, $duration)
	{
		if (!$this->getMatchWorkTime())
		{
			return; // do nothing, dates are okay already
		}
		else
		{
			$calendar = Calendar::getInstance();

			if(!$calendar->isWorkTime($startDate))
			{
				$this->setStartDatePlanUserTimeGmt($calendar->getClosestWorkTime($startDate, true)->toStringGmt());
				$this->setEndDatePlanUserTimeGmt($calendar->calculateEndDate($startDate, $duration)->toStringGmt());
			}
			else
			{
				$this->setEndDatePlanUserTimeGmt($calendar->calculateEndDate($startDate, $duration)->toStringGmt());
			}
		}
	}

	public function getStartDateDelta()
	{
		/** @var DateTime[] $dataOrig */
		$dataOrig = $this->getDataPristine();

		if(!is_object($dataOrig['START_DATE_PLAN']) || !is_object($this->data['START_DATE_PLAN']))
		{
			return INF;
		}

		return $this->data['START_DATE_PLAN']->getTimestamp() - $dataOrig['START_DATE_PLAN']->getTimestamp();
	}

	public function shiftDates($offset)
	{
		$offset = intval($offset);
		if($offset)
		{
			/**
			 * @var DateTime $start
			 */
			$start = clone $this['START_DATE_PLAN'];
			/**
			 * @var DateTime $end
			 */
			$end = clone $this['END_DATE_PLAN'];

			if(!is_object($start) || !is_object($end))
			{
				// it is an infinite task, ignore it
				return;
			}

			$sign = $offset > 0 ? '' : '-';
			$start = $start->add($sign.'T'.abs($offset).'S');
			$end = $end->add($sign.'T'.abs($offset).'S');

			$this->setDataUpdated(array(
				'START_DATE_PLAN' => $start,
				'END_DATE_PLAN' => $end
			));
		}
	}

	public function getEndDateDelta()
	{
		/** @var DateTime[] $dataOrig */
		$dataOrig = $this->getDataPristine();

		if(!is_object($dataOrig['END_DATE_PLAN']) || !is_object($this->data['END_DATE_PLAN']))
		{
			return INF;
		}

		return $this->data['END_DATE_PLAN']->getTimestamp() - $dataOrig['END_DATE_PLAN']->getTimestamp();
	}

	public function dump()
	{
		$pristine = $this->getDataPristine();

		return
			'[' . $this->getId() . '] ' . $this->getFieldValueTitle() . ':' . PHP_EOL . "\t\t" . ' (' .
			$pristine['START_DATE_PLAN'] . ' => ' . $this->data['START_DATE_PLAN']
			. ' - '
			. $pristine['END_DATE_PLAN'] . ' => ' . $this->data['END_DATE_PLAN']
			. ') (' .
			UI::formatTimeAmount($pristine['DURATION_PLAN_SECONDS']) . ' (' . $pristine['DURATION_PLAN_SECONDS'] . ') => ' .
			UI::formatTimeAmount($this->calculateDuration()) . ' (' . $this->calculateDuration() . '))';
	}

	public function getUpdatedData()
	{
		$start = $this->getStartDatePlan(true);
		$end = $this->getEndDatePlan();

		$isStartNull = $start == null;
		$isEndNull = $end == null;

		return array(
			'START_DATE_PLAN' => ($isStartNull? null : $start->toString()),
			'END_DATE_PLAN' => ($isEndNull? null : $end->toString()),
			'DURATION_PLAN_SECONDS' => $this->calculateDuration(),

			'START_DATE_PLAN_STRUCT' => ($isStartNull? array() : $start->getTimeStruct()),
			'END_DATE_PLAN_STRUCT' => ($isEndNull? array() : $end->getTimeStruct()),
		);
	}

	public function exportUpdatedData()
	{
		$data = $this->getUpdatedData();
		$data['ID'] = $this->getId();

		return $data;
	}

	private function matchWorkingTime(DateTime $startDate = null, Datetime $endDate = null, $duration = null)
	{
		if(!$this->getMatchWorkTime())
		{
			return; // do nothing, dates are okay already
		}
		else
		{
			$calendar = Calendar::getInstance();

			if ($startDate && $endDate)
			{
				$this->correctWorkTime($startDate, $endDate, $duration);
			}
			elseif($startDate)
			{
				$this->setStartDatePlanUserTimeGmt($calendar->getClosestWorkTime($startDate, true)->toStringGmt());

				$start = $this->getStartDatePlanGmt(true);
				$end = $this->getEndDatePlanGmt();

				if($end !== null && $start->checkGT($end))
				{
					$this->setEndDatePlanUserTimeGmt($calendar->calculateEndDate($startDate, $end->getTimestamp() - $startDate->getTimestamp())->toStringGmt());
				}
			}
			elseif($endDate)
			{
				$this->setEndDatePlanUserTimeGmt($calendar->getClosestWorkTime($endDate, false)->toStringGmt());

				$start = ($startDate == null? null : $this->getStartDatePlanGmt(true));
				$end = $this->getEndDatePlanGmt();

				if($start !== null && $start->checkGT($end))
				{
					$this->setStartDatePlanUserTimeGmt($calendar->calculateStartDate($end, $endDate->getTimestamp() - $start->getTimestamp())->toStringGmt());
				}
			}
		}
	}

	///////////////////////////////////////////
	///////////////////////////////////////////

	/**
	 * Get task START_DATE_PLAN field of the instance
	 *
	 * @param boolean $getCreatedDateOnNull if set to true, START_DATE_PLAN is empty and CREATED_DATE is not empty, the last will be returned instead of real START_DATE_PLAN
	 *
	 * @return \Bitrix\Tasks\Util\Type\DateTime
	 */
	public function getStartDatePlan($getCreatedDateOnNull = false)
	{
		$date = $this->getStartDateOrCreatedDate($getCreatedDateOnNull);

		if(is_string($date) && !empty($date))
		{
			// $date contains user localtime
			return DateTime::createFromUserTime($date);
		}
		elseif($date instanceof DateTime)
		{
			return clone $date;
		}
		else
		{
			return null;
		}
	}

	/**
	 * Get task END_DATE_PLAN field of the instance
	 *
	 * @return \Bitrix\Tasks\Util\Type\DateTime
	 */
	public function getEndDatePlan()
	{
		if(is_string($this->data['END_DATE_PLAN']) && !empty($this->data['END_DATE_PLAN']))
		{
			return DateTime::createFromUserTime($this->data['END_DATE_PLAN']);
		}
		elseif($this->data['END_DATE_PLAN'] instanceof DateTime)
		{
			return clone $this->data['END_DATE_PLAN'];
		}
		else
		{
			return null;
		}
	}

	/**
	 * Get (not "convert") task START_DATE_PLAN field of the instance as (not "in") GMT
	 *
	 * @param boolean $getCreatedDateOnNull if set to true, START_DATE_PLAN is empty and CREATED_DATE is not empty, the last will be returned instead of real START_DATE_PLAN
	 *
	 * @return \Bitrix\Tasks\Util\Type\DateTime
	 */
	public function getStartDatePlanGmt($getCreatedDateOnNull = false)
	{
		$date = $this->getStartDateOrCreatedDate($getCreatedDateOnNull);

		if ((string)$date == '')
		{
			return null;
		}

		if ($this->startDatePlanGmt === null)
		{
			if ($date instanceof DateTime)
			{
				$date = $date->toString(); //toStringGmt
			}

			$this->startDatePlanGmt = DateTime::createFromUserTimeGmt($date); // string or object allowed here
		}

		return clone $this->startDatePlanGmt;
	}

	/**
	 * Get (not "convert") task END_DATE_PLAN field of the instance as (not "in") GMT
	 *
	 * @return \Bitrix\Tasks\Util\Type\DateTime
	 */
	public function getEndDatePlanGmt()
	{
		if((string)$this->data['END_DATE_PLAN'] == '')
		{
			return null;
		}

		if($this->endDatePlanGmt === null)
		{
			$date = $this->data['END_DATE_PLAN'];

			if($date instanceof DateTime)
			{
				$date = $date->toString();
			}

			$this->endDatePlanGmt = DateTime::createFromUserTimeGmt($date); // string or object allowed here
		}

		return clone $this->endDatePlanGmt;
	}

	/**
	 * Set task START_DATE_PLAN from user time string as GMT
	 *
	 * @param string $timeString Datetime that treated as GMT
	 * @param boolean $preInit
	 * @return void
	 */
	public function setStartDatePlanUserTimeGmt($timeString, $preInit = true)
	{
		if($preInit)
		{
			$this->getDataPristine(); // ensure that pristine data will be separated BEFORE anything changes
		}

		if((string) $timeString == '')
		{
			$this->startDatePlanGmt = null;
			$this->data['START_DATE_PLAN'] = null;
		}
		else
		{
			$this->startDatePlanGmt = DateTime::createFromUserTimeGmt($timeString);
			$this->data['START_DATE_PLAN'] = DateTime::createFromUserTime($timeString);
		}
	}

	/**
	 * Set task END_DATE_PLAN from user time string as GMT
	 *
	 * @param string $timeString Datetime that treated as GMT
	 * @param boolean $preInit
	 * @return void
	 */
	public function setEndDatePlanUserTimeGmt($timeString, $preInit = true)
	{
		if($preInit)
		{
			$this->getDataPristine(); // ensure that pristine data will be separated BEFORE anything changes
		}

		if ((string) $timeString == '')
		{
			$this->endDatePlanGmt = null;
			$this->data['END_DATE_PLAN'] = null;
		}
		else
		{
			$this->endDatePlanGmt = DateTime::createFromUserTimeGmt($timeString);
			$this->data['END_DATE_PLAN'] = DateTime::createFromUserTime($timeString);
		}
	}

	public function getMatchWorkTime()
	{
		return $this->data['MATCH_WORK_TIME'] == 'Y';
	}

	/**
	 * Calculate task duration according to current START_DATE_PLAN, END_DATE_PLAN, MATCH_WORK_TIME and Calendar settings
	 * @return integer
	 */
	public function calculateDuration()
	{
		if (!$this->getStartDatePlan(true) ||
			!$this->getStartDatePlanGmt(true) ||
			!$this->getEndDatePlan() ||
			!$this->getEndDatePlanGmt()) // start date or end date is null
		{
			return 0;
		}

		if ($this->getMatchWorkTime())
		{
			$duration = Calendar::getInstance()->calculateDuration($this->getStartDatePlanGmt(true), $this->getEndDatePlanGmt());
			return ($duration > 0 ? $duration : $this->getEndDatePlanGmt()->getTimestamp() - $this->getStartDatePlanGmt(true)->getTimestamp());
		}
		else
		{
			return ($this->getEndDatePlanGmt()->getTimestamp() - $this->getStartDatePlanGmt(true)->getTimestamp());
		}
	}

	private function getStartDateOrCreatedDate($flag = true)
	{
		$date = null;
		if(empty($this->data['START_DATE_PLAN']) && !empty($this->data['CREATED_DATE']) && $flag)
		{
			$date = $this->data['CREATED_DATE'];
		}
		else
		{
			$date = $this->data['START_DATE_PLAN'];
		}

		return $date;
	}

	public static function getBaseMixin()
	{
		return array(
			'select' => array(
				'START_DATE_PLAN',
				'END_DATE_PLAN',
				'CREATED_DATE',
				'MATCH_WORK_TIME',
				'ALLOW_CHANGE_DEADLINE',
				'DURATION_TYPE',
				'DURATION_PLAN',

				// task fields for php rights checking
				'RESPONSIBLE_ID',
				'CREATED_BY',
				'GROUP_ID',
				'STATUS',
			),
		);
	}

	/**
	 * Cast $date in any legal format into DateTime object in GMT
	 *
	 * @param $date
	 * @return null|DateTime
	 */
	private static function castToDateTimeGmt($date)
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
		elseif ($date instanceof \Bitrix\Main\Type\DateTime)
		{
			// to local time ...
			$date = $date->toString();

			// .. and return as gmt
			return DateTime::createFromUserTimeGmt($date);
		}
		else
		{
			return null;
		}
	}

	/**
	 * @param DateTime $startDate
	 * @return string
	 */
	public static function dateTimeGmtToLocalString($startDate)
	{
		return DateTime::createFromUserTime($startDate->toStringGmt())->toString();
	}
}