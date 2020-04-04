<?php
namespace Bitrix\Timeman\Service;

use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Helper\UserHelper;
use Bitrix\Timeman\Provider\Schedule\ScheduleProvider;
use Bitrix\Timeman\Repository\AbsenceRepository;
use Bitrix\Timeman\Repository\DepartmentRepository;
use Bitrix\Timeman\Repository\Schedule\CalendarRepository;
use Bitrix\Timeman\Repository\Schedule\ScheduleRepository;
use Bitrix\Timeman\Repository\Schedule\ShiftPlanRepository;
use Bitrix\Timeman\Repository\Schedule\ShiftRepository;
use Bitrix\Timeman\Repository\Schedule\ViolationRulesRepository;
use Bitrix\Timeman\Repository\Worktime\WorktimeReportRepository;
use Bitrix\Timeman\Security\UserPermissionsManager;
use Bitrix\Timeman\Service\Agent\AutoCloseWorktimeAgent;
use Bitrix\Timeman\Service\Agent\WorktimeAgentManager;
use Bitrix\Timeman\Service\Notification\InstantMessageNotifier;
use Bitrix\Timeman\Service\Schedule\ViolationRulesService;
use Bitrix\Timeman\Service\Worktime\Notification\WorktimeNotifierFactory;
use Bitrix\Timeman\Service\Schedule\CalendarService;
use Bitrix\Timeman\Service\Schedule\ScheduleAssignmentsService;
use Bitrix\Timeman\Service\Schedule\ScheduleService;
use Bitrix\Timeman\Service\Schedule\ShiftPlanService;
use Bitrix\Timeman\Service\Schedule\ShiftService;
use Bitrix\Timeman\Repository\Worktime\WorktimeRepository;
use Bitrix\Timeman\Service\Worktime\Record\WorktimeRecordManagerFactory;
use Bitrix\Timeman\Service\Worktime\Violation\WorktimeViolationBuilderFactory;
use Bitrix\Timeman\Service\Worktime\Violation\WorktimeViolationManager;
use Bitrix\Timeman\Service\Worktime\WorktimeActionList;
use Bitrix\Timeman\Service\Worktime\WorktimeEventsManager;
use Bitrix\Timeman\Service\Worktime\Notification\WorktimeNotificationService;
use Bitrix\Timeman\Service\Worktime\WorktimeService;
use Bitrix\Timeman\TimemanUrlManager;

class DependencyManager
{
	/** @var ScheduleRepository */
	protected static $scheduleRepository;
	protected static $shiftRepository;
	protected static $shiftPlanRepository;
	protected static $worktimeReportRepository;
	protected static $calendarRepository;
	protected static $worktimeEventsManager;
	protected static $worktimeActionList;
	protected static $violationManager;
	/** @var WorktimeRepository */
	protected static $worktimeRepository;
	protected static $departmentRepository;
	static private $instance;
	protected static $absenceRepository;
	protected static $violationRulesRepository;
	private static $scheduleProvider;

	/**
	 * @return DependencyManager
	 */
	public static function getInstance()
	{
		if (!static::$instance)
		{
			static::$instance = new static();
		}
		return static::$instance;
	}

	protected function __construct()
	{
	}


	/**
	 * @return TimeHelper
	 */
	public function getTimeHelper()
	{
		return TimeHelper::getInstance();
	}

	/**
	 * @return ScheduleProvider
	 */
	public function getScheduleProvider()
	{
		if (!static::$scheduleProvider)
		{
			static::$scheduleProvider = new ScheduleProvider($this->getDepartmentRepository());
		}
		return static::$scheduleProvider;
	}

	public function getScheduleRepository()
	{
		if (!static::$scheduleRepository)
		{
			static::$scheduleRepository = new ScheduleRepository($this->getDepartmentRepository());
		}
		return static::$scheduleRepository;
	}

	/**
	 * @return ViolationRulesRepository
	 */
	public function getViolationRulesRepository()
	{
		if (!static::$violationRulesRepository)
		{
			static::$violationRulesRepository = new ViolationRulesRepository(
				$this->getScheduleRepository(),
				$this->getDepartmentRepository()
			);
		}
		return static::$violationRulesRepository;
	}

	/**
	 * @return ShiftRepository
	 */
	public function getShiftRepository()
	{
		if (!static::$shiftRepository)
		{
			static::$shiftRepository = new ShiftRepository();
		}
		return static::$shiftRepository;
	}

	/**
	 * @return ShiftPlanRepository
	 */
	public function getShiftPlanRepository()
	{
		if (!static::$shiftPlanRepository)
		{
			static::$shiftPlanRepository = new ShiftPlanRepository();
		}
		return static::$shiftPlanRepository;
	}

	/**
	 * @return DepartmentRepository
	 */
	public function getDepartmentRepository()
	{
		if (!static::$departmentRepository)
		{
			static::$departmentRepository = new DepartmentRepository();
		}
		return static::$departmentRepository;
	}

	/**
	 * @param array $options
	 * @return WorktimeService
	 */
	public function getWorktimeService()
	{
		return new WorktimeService(
			new WorktimeRecordManagerFactory(
				$this->getViolationManager(),
				$this->getWorktimeRepository(),
				$this->getShiftPlanRepository()
			),
			$this->getWorktimeAgentManager(),
			$this->getWorktimeActionList(),
			$this->getWorktimeRepository(),
			$this->getViolationRulesRepository(),
			$this->getWorktimeNotificationService()
		);
	}

	public function getWorktimeRepository()
	{
		if (!static::$worktimeRepository)
		{
			static::$worktimeRepository = new WorktimeRepository();
		}
		return static::$worktimeRepository;
	}

	/**
	 * @return CalendarRepository
	 */
	public function getCalendarRepository()
	{
		if (!static::$calendarRepository)
		{
			static::$calendarRepository = new CalendarRepository();
		}
		return static::$calendarRepository;
	}

	/**
	 * @return WorktimeReportRepository
	 */
	public function getWorktimeReportRepository()
	{
		if (!static::$worktimeReportRepository)
		{
			static::$worktimeReportRepository = new WorktimeReportRepository();
		}
		return static::$worktimeReportRepository;
	}

	/**
	 * @return AbsenceRepository
	 */
	public function getAbsenceRepository()
	{
		if (!static::$absenceRepository)
		{
			static::$absenceRepository = new AbsenceRepository();
		}
		return static::$absenceRepository;
	}

	/**
	 * @param $schedule
	 * @return InstantMessageNotifier
	 */
	public function getNotifier($schedule)
	{
		return new InstantMessageNotifier();
	}

	public function getWorktimeEventsManager()
	{
		if (static::$worktimeEventsManager)
		{
			return static::$worktimeEventsManager;
		}
		return new WorktimeEventsManager();
	}

	/**
	 * @return WorktimeViolationManager
	 */
	public function getViolationManager()
	{
		if (static::$violationManager)
		{
			return static::$violationManager;
		}
		return new WorktimeViolationManager(
			new WorktimeViolationBuilderFactory()
		);
	}

	public function getShiftService()
	{
		return new ShiftService(
			$this->getShiftRepository(),
			$this->getShiftPlanRepository(),
			$this->getWorktimeAgentManager()
		);
	}

	public function getWorktimeAgentManager()
	{
		return new WorktimeAgentManager($this->getViolationRulesRepository(), $this->getShiftPlanRepository());
	}

	public function getScheduleService()
	{
		return new ScheduleService(
			new CalendarService(new CalendarRepository()),
			new ShiftService($this->getShiftRepository(), $this->getShiftPlanRepository(), $this->getWorktimeAgentManager()),
			$this->getScheduleAssignmentsService(),
			$this->getViolationRulesService(),
			$this->getWorktimeService(),
			$this->getScheduleProvider()
		);
	}

	public function getShiftPlanService()
	{
		return new ShiftPlanService(
			new ShiftRepository(),
			new ShiftPlanRepository(),
			$this->getWorktimeAgentManager()
		);
	}

	public function getCalendarService()
	{
		return new CalendarService(new CalendarRepository());
	}

	/**
	 * @return WorktimeNotificationService
	 */
	public function getWorktimeNotificationService()
	{
		return new WorktimeNotificationService(
			$this->getScheduleRepository(),
			new WorktimeNotifierFactory(),
			UserHelper::getInstance(),
			TimeHelper::getInstance(),
			$this->getUrlManager()
		);
	}

	public function getWorktimeActionList()
	{
		if (static::$worktimeActionList)
		{
			return static::$worktimeActionList;
		}
		return new WorktimeActionList(
			$this->getShiftPlanRepository(),
			$this->getWorktimeRepository(),
			$this->getScheduleProvider()
		);
	}

	/**
	 * @return ScheduleAssignmentsService
	 */
	private function getScheduleAssignmentsService()
	{
		return new ScheduleAssignmentsService(
			$this->getScheduleProvider(),
			new ShiftRepository(),
			new ShiftPlanRepository(),
			new DepartmentRepository()
		);
	}

	public function getViolationRulesService()
	{
		return new ViolationRulesService($this->getViolationRulesRepository(), $this->getWorktimeAgentManager());
	}

	/**
	 * @param \CUser $user
	 * @return UserPermissionsManager
	 */
	public function getUserPermissionsManager($user)
	{
		return UserPermissionsManager::getInstanceByUser($user);
	}

	/**
	 * @return TimemanUrlManager
	 */
	public function getUrlManager()
	{
		return TimemanUrlManager::getInstance();
	}

	public function getAutoCloseWorktimeAgent()
	{
		return new AutoCloseWorktimeAgent(
			$this->getWorktimeRepository(),
			$this->getWorktimeService()
		);
	}
}