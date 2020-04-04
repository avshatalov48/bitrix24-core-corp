<?php
namespace Bitrix\Timeman\Service\Worktime\Violation;

use Bitrix\Timeman\Service\DependencyManager;

class WorktimeViolationBuilderFactory
{
	/** @var \Bitrix\Timeman\Provider\Schedule\ScheduleProvider */
	private $scheduleProvider;
	/** @var \Bitrix\Timeman\Repository\AbsenceRepository */
	private $absenceRepository;
	/** @var \Bitrix\Timeman\Repository\Worktime\WorktimeRepository */
	private $worktimeRepository;
	/** @var \Bitrix\Timeman\Repository\DepartmentRepository */
	private $departmentRepository;
	/** @var \Bitrix\Timeman\Repository\Schedule\ShiftPlanRepository */
	private $shiftPlanRepository;
	/** @var \Bitrix\Timeman\Repository\Schedule\ShiftRepository */
	private $shiftRepository;
	/** @var \Bitrix\Timeman\Repository\Schedule\CalendarRepository */
	private $calendarRepository;

	public function __construct(
		$calendarRepository = null,
		$scheduleProvider = null,
		$absenceRepository = null,
		$departmentRepository = null,
		$shiftPlanRepository = null,
		$shiftRepository = null,
		$worktimeRepository = null
	)
	{
		$this->calendarRepository = $calendarRepository ?: DependencyManager::getInstance()->getCalendarRepository();
		$this->scheduleProvider = $scheduleProvider ?: DependencyManager::getInstance()->getScheduleProvider();
		$this->absenceRepository = $absenceRepository ?: DependencyManager::getInstance()->getAbsenceRepository();
		$this->worktimeRepository = $worktimeRepository ?: DependencyManager::getInstance()->getWorktimeRepository();
		$this->departmentRepository = $departmentRepository ?: DependencyManager::getInstance()->getDepartmentRepository();
		$this->shiftPlanRepository = $shiftPlanRepository ?: DependencyManager::getInstance()->getShiftPlanRepository();
		$this->shiftRepository = $shiftRepository ?: DependencyManager::getInstance()->getShiftRepository();
	}

	public function createFixedScheduleViolationBuilder(WorktimeViolationParams $violationParams)
	{
		return new FixedScheduleViolationBuilder(
			$violationParams,
			$this->calendarRepository,
			$this->scheduleProvider,
			$this->absenceRepository,
			$this->worktimeRepository,
			$this->departmentRepository
		);
	}

	public function createShiftedScheduleViolationBuilder(WorktimeViolationParams $violationParams)
	{
		return new ShiftedScheduleViolationBuilder(
			$violationParams,
			$this->calendarRepository,
			$this->scheduleProvider,
			$this->absenceRepository,
			$this->worktimeRepository,
			$this->shiftPlanRepository,
			$this->shiftRepository
		);
	}

	public function createFlextimeScheduleViolationBuilder(WorktimeViolationParams $violationParams)
	{
		return new FlexTimeScheduleViolationBuilder(
			$violationParams,
			$this->calendarRepository,
			$this->scheduleProvider,
			$this->absenceRepository
		);
	}
}