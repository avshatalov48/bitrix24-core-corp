<?php
namespace Bitrix\Timeman\Service\Worktime\Violation;

use Bitrix\Main\Error;
use Bitrix\Main\ObjectException;
use Bitrix\Timeman\Form\Schedule\ShiftPlanForm;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Repository\AbsenceRepository;
use Bitrix\Timeman\Repository\Schedule\CalendarRepository;
use Bitrix\Timeman\Repository\Schedule\ScheduleRepository;
use Bitrix\Timeman\Repository\Schedule\ShiftPlanRepository;
use Bitrix\Timeman\Repository\Schedule\ShiftRepository;
use Bitrix\Timeman\Repository\Worktime\WorktimeRepository;

class WorktimeViolationManager
{
	/** @var WorktimeViolationBuilder */
	private $fixedViolationBuilder;
	/** @var WorktimeViolationBuilder */
	private $shiftedViolationBuilder;
	/** @var WorktimeViolationBuilder */
	private $flextimeViolationBuilder;

	/** @var ShiftRepository */
	private $shiftRepository;
	/** @var ShiftPlanRepository */
	private $shiftPlanRepository;
	/** @var WorktimeRepository */
	private $worktimeRepository;
	private $calendarRepository;
	private $scheduleRepository;
	private $absenceRepository;

	public function __construct(
		ShiftRepository $shiftRepository,
		ShiftPlanRepository $shiftPlanRepository,
		WorktimeRepository $worktimeRepository,
		CalendarRepository $calendarRepository,
		ScheduleRepository $scheduleRepository,
		AbsenceRepository $absenceRepository)
	{
		$this->shiftRepository = $shiftRepository;
		$this->shiftPlanRepository = $shiftPlanRepository;
		$this->worktimeRepository = $worktimeRepository;
		$this->calendarRepository = $calendarRepository;
		$this->scheduleRepository = $scheduleRepository;
		$this->absenceRepository = $absenceRepository;
	}

	/**
	 * @return WorktimeViolation[]
	 */
	public function buildViolations(WorktimeViolationParams $params, $types = [])
	{
		return $this->getViolationBuilder($params)->buildViolations($types);
	}

	/**
	 * @param WorktimeViolationParams $params
	 * @param \DateTime $fromDateTime
	 * @param \DateTime $toDateTime
	 * @return WorktimeViolationResult
	 * @throws ObjectException
	 */
	public function buildPeriodTimeLackViolation($params, $fromDateTime, $toDateTime)
	{
		return $this->getViolationBuilder($params)
			->buildPeriodTimeLackViolation($params, $fromDateTime, $toDateTime);
	}

	public function buildEditedWorktimeWarnings(WorktimeViolationParams $params, $checkAllowedDelta = true)
	{
		return $this->getViolationBuilder($params)
			->buildEditedWorktimeWarnings($checkAllowedDelta);
	}

	/**
	 * @param $shiftId
	 * @param $userId
	 * @param $dateFormatted
	 * @return WorktimeViolationResult
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function buildMissedShiftViolation($shiftId, $userId, $dateFormatted)
	{
		$result = new WorktimeViolationResult();
		$shiftPlanForm = new ShiftPlanForm();
		$shiftPlanForm->userId = $userId;
		$shiftPlanForm->shiftId = $shiftId;
		$shiftPlanForm->dateAssignedFormatted = $dateFormatted;
		if (!$shiftPlanForm->validate())
		{
			return $result->addError(new Error('', WorktimeViolationResult::ERROR_CODE_WRONG_PARAMETERS));
		}

		$shift = $this->shiftRepository->findByIdWithSchedule($shiftId);
		if (!$shift)
		{
			return $result->addError(new Error('', WorktimeViolationResult::ERROR_CODE_SHIFT_NOT_FOUND));
		}

		return $this->getViolationBuilder(
			(new WorktimeViolationParams())
				->setSchedule($shift->obtainSchedule())
				->setViolationRules($shift->obtainSchedule() ? $shift->obtainSchedule()->obtainScheduleViolationRules() : null)
				->setShift($shift)
		)
			->buildMissedShiftViolation($shift, $userId, $dateFormatted);
	}

	/**
	 * @param WorktimeViolationParams $violationParams
	 * @return WorktimeViolationBuilder
	 * @throws ObjectException
	 */
	protected function getViolationBuilder(WorktimeViolationParams $violationParams)
	{
		if (!$violationParams->getSchedule())
		{
			throw new ObjectException(Schedule::class . ' is required to instantiate WorktimeViolationBuilder');
		}
		if (Schedule::isScheduleFixed($violationParams->getSchedule()))
		{
			if (!$this->fixedViolationBuilder)
			{
				$this->fixedViolationBuilder = new FixedScheduleViolationBuilder(
					$violationParams,
					$this->shiftRepository,
					$this->shiftPlanRepository,
					$this->worktimeRepository,
					$this->calendarRepository,
					$this->scheduleRepository,
					$this->absenceRepository
				);
			}
			else
			{
				$this->fixedViolationBuilder->setWorktimeViolationParams($violationParams);
			}
			return $this->fixedViolationBuilder;
		}
		elseif (Schedule::isScheduleShifted($violationParams->getSchedule()))
		{
			if (!$this->shiftedViolationBuilder)
			{
				$this->shiftedViolationBuilder = new ShiftedScheduleViolationBuilder(
					$violationParams,
					$this->shiftRepository,
					$this->shiftPlanRepository,
					$this->worktimeRepository,
					$this->calendarRepository,
					$this->scheduleRepository,
					$this->absenceRepository
				);
			}
			else
			{
				$this->shiftedViolationBuilder->setWorktimeViolationParams($violationParams);
			}
			return $this->shiftedViolationBuilder;
		}
		elseif (Schedule::isScheduleFlexible($violationParams->getSchedule()))
		{
			if (!$this->flextimeViolationBuilder)
			{
				$this->flextimeViolationBuilder = new FlexTimeScheduleViolationBuilder(
					$violationParams,
					$this->shiftRepository,
					$this->shiftPlanRepository,
					$this->worktimeRepository,
					$this->calendarRepository,
					$this->scheduleRepository,
					$this->absenceRepository
				);
			}
			else
			{
				$this->flextimeViolationBuilder->setWorktimeViolationParams($violationParams);
			}
			return $this->flextimeViolationBuilder;
		}
	}

	public function getWorktimeRepository()
	{
		return $this->worktimeRepository;
	}

	public function getShiftPlanRepository()
	{
		return $this->shiftPlanRepository;
	}

	public function getCalendarRepository()
	{
		return $this->calendarRepository;
	}

	public function getScheduleRepository()
	{
		return $this->scheduleRepository;
	}

	public function getAbsenceRepository()
	{
		return $this->absenceRepository;
	}
}