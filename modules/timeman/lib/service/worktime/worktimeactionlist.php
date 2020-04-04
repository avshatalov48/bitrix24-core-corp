<?php
namespace Bitrix\Timeman\Service\Worktime;

use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\ScheduleTable;
use Bitrix\Timeman\Model\Schedule\Shift\Shift;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord;
use Bitrix\Timeman\Provider\Schedule\ScheduleProvider;
use Bitrix\Timeman\Repository\Worktime\WorktimeRepository;

class WorktimeActionList
{
	/** @var WorktimeAction[] */
	private $actions = [];
	private $userId;

	/** @var \DateTime */
	private $userDateTime;
	/** @var Schedule[] */
	private $schedules = [];

	/** @var WorktimeRepository */
	private $worktimeRepository;
	/** @var ScheduleProvider */
	private $scheduleProvider;
	/**
	 * @var Schedule
	 */
	private $recordSchedule;

	public function __construct(WorktimeRepository $worktimeRepository, ScheduleProvider $scheduleProvider)
	{
		$this->worktimeRepository = $worktimeRepository;
		$this->scheduleProvider = $scheduleProvider;
		$this->actions = [];
	}

	/**
	 * @param $userId
	 * @param null $currentUserDateTime
	 * @return $this
	 */
	public function buildPossibleActionsListForUser($userId)
	{
		$this->userId = $userId;
		$this->userDateTime = $this->getUserDateTime($this->userId);

		$record = $this->findLastRecord();
		$this->schedules = $this->findSchedules($this->userId, $record);

		$this->actions = $this->buildActions($record);
		return $this;
	}

	/**
	 * @param WorktimeRecord $record
	 * @return WorktimeAction[]
	 */
	private function buildActions($record)
	{
		// build start
		if (!$record)
		{
			$action = WorktimeAction::createStartAction($this->userId)
				->setSchedule($this->getRelevantSchedule());
			$action->setShift($this->getRelevantShift($action->getSchedule()));
			return [
				$action,
			];
		}
		/** @var WorktimeAction[] $actions */
		$actions = [];
		$recordSchedule = $this->getRecordSchedule();

		if ($record->getRecordedStopTimestamp() > 0)
		{
			if ($this->isNextDayStarted($record))
			{
				$actions[] = WorktimeAction::createStartAction($this->userId);
			}
		}
		// build start


		// build stop
		if ($record->getRecordedStopTimestamp() === 0)
		{
			$actions[] = WorktimeAction::createStopAction($this->userId);
		}
		// build stop


		// build pause
		if ($record->getRecordedStopTimestamp() === 0 && !$record->isExpired($recordSchedule) && !$record->isPaused())
		{
			$actions[] = WorktimeAction::createPauseAction($this->userId);
		}
		// build pause


		// build relaunch
		if ($record->isEligibleToReopen($recordSchedule))
		{
			$actions[] = WorktimeAction::createRelaunchAction($this->userId);
		}
		// build relaunch
		// build continue
		if ($record->isPaused() && !$record->isExpired($recordSchedule))
		{
			$actions[] = WorktimeAction::createContinueAction($this->userId);
		}
		// build continue


		// build edit
		if ($record->isEligibleToEdit($recordSchedule))
		{
			$actions[] = WorktimeAction::createEditAction($this->userId);
		}
		// build edit


		foreach ($actions as $action)
		{
			if ($action->isStart())
			{
				$action->setSchedule($this->getRelevantSchedule());
				$action->setShift($this->getRelevantShift($action->getSchedule()));
			}
			else
			{
				$action->setRecord($record);
				$action->setSchedule($record ? $this->getRecordSchedule() : null);
				$action->setShift($record ? $this->getShiftById($record->getShiftId()) : null);
			}
		}

		return $actions;
	}

	private function getShiftById($id)
	{
		if ($id <= 0)
		{
			return null;
		}
		foreach ($this->schedules as $schedule)
		{
			foreach ($schedule->obtainShifts() as $shift)
			{
				if ($shift->getId() == $id)
				{
					return $shift;
				}
			}
		}
		return null;
	}

	/**
	 * @return Schedule[]
	 */
	public function getSchedules()
	{
		return $this->schedules;
	}

	private function getShiftStartDateTime($shift, $withOffset = true)
	{
		return $this->getShiftStartEndByGivenEventTime($shift, $withOffset)[0];
	}

	private function getRelevantSchedule()
	{
		foreach ($this->schedules as $schedule)
		{
			if ($schedule->isFlexible())
			{
				return $schedule;
			}
		}
		return reset($this->schedules);
	}

	/**
	 * @param Schedule $schedule
	 * @return Shift|null
	 */
	private function getRelevantShift($schedule)
	{
		if (!$schedule)
		{
			return null;
		}
		$recordDayShift = $schedule->getShiftForWeekDay(TimeHelper::getInstance()->getDayOfWeek($this->userDateTime));
		return $recordDayShift;
	}

	private function getRelevantShifts()
	{
		$resShifts = [];
		foreach ($this->schedules as $schedule)
		{
			/** @var Schedule $schedule */
			$shifts = $schedule->obtainShifts();
			foreach ($shifts as $shift)
			{
				$resShifts[] = $shift;
				if ($this->isGivenUserTimeInShiftRange($shift))
				{
					$resShifts[] = $shift;
				};
			}
		}

		return $resShifts;
	}

	private function isGivenUserTimeInShiftRange($shift)
	{
		return $this->getShiftStartDateTime($shift) !== null;
	}

	/**
	 * @param Shift $shift
	 * @param \DateTime|null $userDateTime
	 * @return array
	 */
	private function getShiftStartEndByGivenEventTime($shift, $withOffset = true)
	{
		$daysAdd = ['', '-1 day', '+1 day'];
		foreach ($daysAdd as $dayAdd)
		{
			$startDateTime = clone $this->userDateTime;
			$startDateTime->setTime($shift->getStartHours(), $shift->getStartMinutes(), $shift->getStartSeconds());
			$startDateTime->add(\DateInterval::createFromDateString(
				'-' . ($withOffset ? $this->getAllowedStartOffset($shift) : '') . ' seconds, ' . $dayAdd)
			);

			$endDateTime = clone $this->userDateTime;
			$endDateTime->setTime($shift->getStartHours(), $shift->getStartMinutes(), $shift->getStartSeconds());
			$endDelta = $shift->getDuration();
			if ($withOffset)
			{
				$endDelta = $endDelta + $this->getAllowedEndOffset($shift);
			}
			$endDateTime->add(\DateInterval::createFromDateString(
				'+' . $endDelta . ' seconds, ' . $dayAdd)
			);

			if ($this->userDateTime->format('U') >= $startDateTime->format('U')
				&& $this->userDateTime->format('U') <= $endDateTime->format('U'))
			{
				return [$startDateTime, $endDateTime];
			}
		}
		return [null, null];
	}

	/**
	 * @param Shift $shift
	 * @return float
	 */
	private function getAllowedStartOffset($shift)
	{
		$schedule = $this->getScheduleById($shift['SCHEDULE_ID']);
		return Schedule::getScheduleRestriction($schedule, ScheduleTable::WORKTIME_RESTRICTION_MAX_START_END_OFFSET);
	}


	/**
	 * @param Shift $shift
	 * @return float
	 */
	private function getAllowedEndOffset($shift)
	{
		$schedule = $this->getScheduleById($shift['SCHEDULE_ID']);
		return Schedule::getScheduleRestriction($schedule, ScheduleTable::WORKTIME_RESTRICTION_MAX_START_END_OFFSET);
	}

	public function getScheduleById($id)
	{
		foreach ($this->schedules as $schedule)
		{
			if ($schedule->getId() == $id)
			{
				return $schedule;
			}
		}
		return null;
	}

	protected function getUserDateTime($userId)
	{
		return TimeHelper::getInstance()->getUserDateTimeNow($userId);
	}

	/**
	 * @param $userId
	 * @param WorktimeRecord $record
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function findSchedules($userId, $record = null)
	{
		$result = [];
		$schedules = $this->scheduleProvider->findSchedulesByUserId($userId);
		foreach ($schedules as $schedule)
		{
			/** @var Schedule $schedule */
			$result[$schedule->getId()] = $schedule;
		}
		if ($record && $record->getScheduleId() > 0)
		{
			if (isset($result[$record->getScheduleId()]))
			{
				$this->recordSchedule = $result[$record->getScheduleId()];
			}
			else
			{
				$recordSchedule = $this->scheduleProvider->findByIdWith($record->getScheduleId(), ['SHIFTS', 'SCHEDULE_VIOLATION_RULES']);
				if ($recordSchedule)
				{
					$this->recordSchedule = $recordSchedule;
				}
			}
		}
		return array_filter($result);
	}

	/**
	 * @return array|WorktimeAction[]
	 */
	public function getStopActions()
	{
		$res = [];
		foreach ($this->actions as $action)
		{
			if ($action->isStop())
			{
				$res[] = $action;
			}
		}
		return $res;
	}

	/**
	 * @return array|WorktimeAction[]
	 */
	public function getEditActions()
	{
		$res = [];
		foreach ($this->actions as $action)
		{
			if ($action->isEdit())
			{
				$res[] = $action;
			}
		}
		return $res;
	}

	/**
	 * @return array|WorktimeAction[]
	 */
	public function getContinueActions()
	{
		$res = [];
		foreach ($this->actions as $action)
		{
			if ($action->isContinue())
			{
				$res[] = $action;
			}
		}
		return $res;
	}

	/**
	 * @return array|WorktimeAction[]
	 */
	public function getRelaunchActions()
	{
		$res = [];
		foreach ($this->actions as $action)
		{
			if ($action->isRelaunch())
			{
				$res[] = $action;
			}
		}
		return $res;
	}

	/**
	 * @return array|WorktimeAction[]
	 */
	public function getPauseActions()
	{
		$res = [];
		foreach ($this->actions as $action)
		{
			if ($action->isPause())
			{
				$res[] = $action;
			}
		}
		return $res;
	}

	/**
	 * @return array|WorktimeAction[]
	 */
	public function getStartActions()
	{
		$res = [];
		foreach ($this->actions as $action)
		{
			if ($action->isStart())
			{
				$res[] = $action;
			}
		}
		return $res;
	}

	/**
	 * @return array|WorktimeAction[]
	 */
	public function getAllActions()
	{
		return $this->actions;
	}

	protected function findLastRecord()
	{
		return $this->worktimeRepository->findLatestRecord($this->userId);
	}

	private function isNextDayStarted(WorktimeRecord $record)
	{
		$helper = TimeHelper::getInstance();
		$nextDayStart = clone $record->buildRecordedStartDateTime();
		$nextDayStart->add(new \DateInterval('P1D'));
		$nextDayStart->setTime(0, 0, 0);

		$nowDateTime = $helper->getUserDateTimeNow($record->getUserId());

		return $nowDateTime->getTimestamp() >= $nextDayStart->getTimestamp();
	}

	protected function getRecordSchedule()
	{
		return $this->recordSchedule;
	}
}