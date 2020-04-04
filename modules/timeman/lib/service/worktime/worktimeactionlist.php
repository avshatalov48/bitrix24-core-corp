<?php
namespace Bitrix\Timeman\Service\Worktime;

use Bitrix\Main\Type\Date;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\Shift\Shift;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord;
use Bitrix\Timeman\Provider\Schedule\ScheduleProvider;
use Bitrix\Timeman\Repository\Schedule\ShiftPlanRepository;
use Bitrix\Timeman\Repository\Worktime\WorktimeRepository;

class WorktimeActionList
{
	/** @var WorktimeRepository */
	private $worktimeRepository;
	/** @var ScheduleProvider */
	private $scheduleProvider;
	/** @var ShiftPlanRepository */
	private $shiftPlanRepository;

	/** @var WorktimeAction[] */
	private $actions = [];
	private $userId;

	/** @var \DateTime */
	private $userDateTime;
	/** @var Schedule[] */
	private $schedules = [];
	/** @var Schedule */
	private $recordSchedule;

	public function __construct(
		ShiftPlanRepository $shiftPlanRepository,
		WorktimeRepository $worktimeRepository,
		ScheduleProvider $scheduleProvider)
	{
		$this->shiftPlanRepository = $shiftPlanRepository;
		$this->worktimeRepository = $worktimeRepository;
		$this->scheduleProvider = $scheduleProvider;
		$this->actions = [];
	}

	/**
	 * @param $userId
	 * @param null $currentUserDateTime
	 * @return $this
	 */
	public function buildPossibleActionsListForUser($userId, $userDate = null)
	{
		$this->userId = $userId;
		$this->userDateTime = $userDate instanceof \DateTime ? $userDate : TimeHelper::getInstance()->getUserDateTimeNow($this->userId);

		$record = $this->findLastRecord();
		$this->schedules = $this->findSchedules($this->userId);
		$this->fillRecordSchedule($record);

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
		if (!$record || $record->getRecordedStartTimestamp() > TimeHelper::getInstance()->getUtcNowTimestamp())
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
		$recordShift = $record ? $this->getShiftById($record->getShiftId()) : null;
		if ($recordShift === null && $record && $record->obtainShift())
		{
			$recordShift = $record->obtainShift();
		}

		if ($record->getRecordedStopTimestamp() > 0)
		{
			if (Schedule::isScheduleShifted($recordSchedule) && $recordSchedule instanceof Schedule)
			{
				if ($record->isEligibleToStartNext($recordSchedule, $recordShift))
				{
					$actions[] = WorktimeAction::createStartAction($this->userId);
				}
			}
			else
			{
				// we have two buttons at once - start and continue
				if ($this->isNextDayStarted($record))
				{
					$actions[] = WorktimeAction::createStartAction($this->userId);
				}
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
		if ($record->getRecordedStopTimestamp() === 0 && !$record->isExpired($recordSchedule, $recordShift) && !$record->isPaused())
		{
			$actions[] = WorktimeAction::createPauseAction($this->userId);
		}
		// build pause


		// build reopen
		if ($record->isEligibleToReopen($recordSchedule, $recordShift))
		{
			$actions[] = WorktimeAction::createReopenAction($this->userId);
		}
		// build reopen
		// build continue
		if ($record->isPaused() && !$record->isExpired($recordSchedule, $recordShift))
		{
			$actions[] = WorktimeAction::createContinueAction($this->userId);
		}
		// build continue


		// build edit
		if ($record->isEligibleToEdit($recordSchedule, $recordShift))
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
				$action->setShift($recordShift);
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

	private function getRelevantSchedule()
	{
		$fixedSchedules = [];
		$shiftedSchedules = [];
		$flexibleSchedules = [];
		foreach ($this->schedules as $schedule)
		{
			if ($schedule->isFixed())
			{
				$fixedSchedules[] = $schedule;
			}
			elseif ($schedule->isFlexible())
			{
				$flexibleSchedules[] = $schedule;
			}
			elseif ($schedule->isShifted())
			{
				$shiftedSchedules[] = $schedule;
			}
		}

		if (!empty($flexibleSchedules))
		{
			return reset($flexibleSchedules);
		}
		if (!empty($shiftedSchedules))
		{
			/*-*/// more than one shifted schedule - get random
			return reset($shiftedSchedules);
		}

		if (!empty($fixedSchedules))
		{
			foreach ($fixedSchedules as $fixedSchedule)
			{
				/** @var Schedule $fixedSchedule */
				if ($fixedSchedule->getRelevantShiftByStart($this->userDateTime))
				{
					return $fixedSchedule;
				}
			}
			return reset($fixedSchedules);
		}
		return reset($this->schedules);
	}

	/**
	 * @param Schedule $relevantSchedule
	 * @param WorktimeRecord $record
	 * @param Schedule $recordSchedule
	 * @param Shift $recordShift
	 * @return null
	 */
	private function getRelevantShift($relevantSchedule)
	{
		if (!$relevantSchedule)
		{
			return null;
		}
		if ($relevantSchedule->isFixed())
		{
			return $relevantSchedule->getRelevantShiftByStart($this->userDateTime);
		}

		if ($relevantSchedule->isShifted())
		{
			$relevantShifts = $relevantSchedule->getAllShiftsByTime($this->userDateTime);
			if (count($relevantShifts) <= 1)
			{
				return empty($relevantShifts) ? null : reset($relevantShifts);
			}
			// has more than 1 shift
			foreach ($relevantShifts as $relevantShift)
			{
				if ($this->hasShiftPlan($relevantShift, $relevantSchedule))
				{
					return $relevantShift;
				}
			}
			return $relevantSchedule->getShiftByTime($this->userDateTime);
		}

		return null;
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

	/**
	 * @param $userId
	 * @param WorktimeRecord $record
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function findSchedules($userId)
	{
		$result = [];
		$schedules = $this->scheduleProvider->findSchedulesByUserId($userId);
		foreach ($schedules as $schedule)
		{
			/** @var Schedule $schedule */
			$result[$schedule->getId()] = $schedule;
		}
		return array_filter($result);
	}

	/**
	 * @return WorktimeAction[]
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
	 * @return WorktimeAction[]
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
	 * @return WorktimeAction[]
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
	 * @return WorktimeAction[]
	 */
	public function getReopenActions()
	{
		$res = [];
		foreach ($this->actions as $action)
		{
			if ($action->isReopen())
			{
				$res[] = $action;
			}
		}
		return $res;
	}

	/**
	 * @return WorktimeAction[]
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
	 * @return WorktimeAction[]
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
	 * @return WorktimeAction[]
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

	/**
	 * @param WorktimeRecord $record
	 */
	protected function fillRecordSchedule($record)
	{
		if (!$record || $record->getScheduleId() <= 0)
		{
			return;
		}

		if ($this->getScheduleById($record->getScheduleId()))
		{
			$this->recordSchedule = $this->getScheduleById($record->getScheduleId());
		}
		else
		{
			$this->recordSchedule = $this->scheduleProvider->findByIdWith($record->getScheduleId(), ['SHIFTS', 'SCHEDULE_VIOLATION_RULES']);
		}
	}

	/**
	 * @param Shift $shift
	 * @param Schedule $schedule
	 * @return bool
	 */
	private function hasShiftPlan($shift, $schedule)
	{
		$shiftStartDate = $shift->buildStartDateTimeByArrivalDateTime($this->userDateTime, $schedule);
		$shiftStartDate->setTimezone(new \DateTimeZone('UTC'));
		return (bool)$this->shiftPlanRepository->findActiveByComplexId(
			$shift->getId(),
			$this->userId,
			new Date($shiftStartDate->format('Y-m-d'), 'Y-m-d')
		);
	}
}