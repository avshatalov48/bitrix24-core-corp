<?php
namespace Bitrix\Timeman\Service\Worktime\Violation;


use Bitrix\Timeman\Model\Schedule\Shift\Shift;
use Bitrix\Timeman\Model\Schedule\Violation\ViolationRules;
use Bitrix\Timeman\Provider\Schedule\ScheduleProvider;
use Bitrix\Timeman\Repository\AbsenceRepository;
use Bitrix\Timeman\Repository\Schedule\CalendarRepository;
use Bitrix\Timeman\Repository\Schedule\ShiftPlanRepository;
use Bitrix\Timeman\Repository\Schedule\ShiftRepository;
use Bitrix\Timeman\Repository\Worktime\WorktimeRepository;

class ShiftedScheduleViolationBuilder extends WorktimeViolationBuilder
{
	/** @var ShiftPlanRepository */
	private $shiftPlanRepository;
	/** @var WorktimeRepository */
	private $worktimeRepository;
	/** @var ShiftRepository */
	private $shiftRepository;
	private $plan = false;

	public function __construct(
		WorktimeViolationParams $params,
		CalendarRepository $calendarRepository,
		ScheduleProvider $scheduleProvider,
		AbsenceRepository $absenceRepository,
		WorktimeRepository $worktimeRepository,
		ShiftPlanRepository $shiftPlanRepository,
		ShiftRepository $shiftRepository
	)
	{
		parent::__construct($params, $calendarRepository, $scheduleProvider, $absenceRepository);
		$this->worktimeRepository = $worktimeRepository;
		$this->shiftPlanRepository = $shiftPlanRepository;
		$this->shiftRepository = $shiftRepository;
	}

	/**
	 * @param Shift $shift
	 * @param $userId
	 * @param $dateFormatted
	 * @return WorktimeViolationBuilder|mixed
	 */
	public function buildMissedShiftViolation()
	{
		$result = new WorktimeViolationResult();

		$schedule = $this->getSchedule();
		$shiftPlan = $this->getShiftPlan();
		$shift = $this->getShift();
		if (!$shiftPlan || !$shift || !$schedule || $this->getRecord())
		{
			return $result;
		}
		$userWasAbsent = $this->isUserWasAbsent($shiftPlan->getUserId(), $shift->buildUtcStartByShiftplan($shiftPlan));
		if ($userWasAbsent)
		{
			return $result;
		}
		if ($this->getViolationRules()->isMissedShiftsControlEnabled())
		{
			$violation = $this->createViolation(WorktimeViolation::TYPE_MISSED_SHIFT);
			$result->addViolation($violation);
		}

		return $result
			->setShift($shift)
			->setShiftPlan($shiftPlan)
			->setSchedule($shift->obtainSchedule());
	}

	protected function getShiftPlan()
	{
		if ($this->plan === false)
		{
			$this->plan = parent::getShiftPlan();
			if (!$this->plan)
			{
				$this->plan = $this->shiftPlanRepository->findActiveByRecord($this->getRecord());
			}
		}
		return $this->plan;
	}

	protected function buildStartViolations()
	{
		$shift = $this->getShift();
		if (!$shift)
		{
			return [];
		}
		$record = $this->getRecord();
		$recordedStartSeconds = $this->getTimeHelper()->getSecondsFromDateTime($record->buildRecordedStartDateTime());
		$violations = [];
		$offset = $this->getViolationRules()['MAX_SHIFT_START_DELAY'];
		if (ViolationRules::isViolationConfigured($offset))
		{
			if (($recordedStartSeconds - $shift->getWorkTimeStart()) > $offset)
			{
				$violations[] = $this->createViolation(
					WorktimeViolation::TYPE_SHIFT_LATE_START,
					$record->getRecordedStartTimestamp(),
					$recordedStartSeconds - $shift->getWorkTimeStart() - $offset
				);
			}
		}
		return $violations;
	}

	protected function isWorkingByShiftPlan()
	{
		return $this->getShiftPlan() !== null;
	}

	protected function skipViolationsCheck()
	{
		return parent::skipViolationsCheck()
			   || !($this->isWorkingByShiftPlan());
	}
}