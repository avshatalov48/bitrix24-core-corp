<?php
namespace Bitrix\Timeman\Service;

use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Helper\UserHelper;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\ScheduleCollection;
use Bitrix\Timeman\Model\Schedule\Shift\Shift;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord;
use Bitrix\Timeman\Provider\Schedule\ScheduleProvider;
use Bitrix\Timeman\Provider\Schedule\ShiftPlanProvider;
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
use Bitrix\Timeman\Service\Worktime\Action\ShiftsManager;
use Bitrix\Timeman\Service\Worktime\Action\WorktimeActionList;
use Bitrix\Timeman\Service\Worktime\Action\WorktimeRecordManager;
use Bitrix\Timeman\Service\Worktime\Notification\WorktimeNotifierFactory;
use Bitrix\Timeman\Service\Schedule\CalendarService;
use Bitrix\Timeman\Service\Schedule\ScheduleAssignmentsService;
use Bitrix\Timeman\Service\Schedule\ScheduleService;
use Bitrix\Timeman\Service\Schedule\ShiftPlanService;
use Bitrix\Timeman\Service\Schedule\ShiftService;
use Bitrix\Timeman\Repository\Worktime\WorktimeRepository;
use Bitrix\Timeman\Service\Worktime\Record\WorktimeManagerFactory;
use Bitrix\Timeman\Service\Worktime\Violation\WorktimeViolationBuilderFactory;
use Bitrix\Timeman\Service\Worktime\Violation\WorktimeViolationManager;
use Bitrix\Timeman\Service\Worktime\WorktimeEventsManager;
use Bitrix\Timeman\Service\Worktime\Notification\WorktimeNotificationService;
use Bitrix\Timeman\Service\Worktime\WorktimeLiveFeedManager;
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

	protected function __construct()
	{
	}

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

	public function getViolationRulesRepository(): ViolationRulesRepository
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

	public function getShiftRepository(): ShiftRepository
	{
		if (!static::$shiftRepository)
		{
			static::$shiftRepository = new ShiftRepository();
		}
		return static::$shiftRepository;
	}

	public function getShiftPlanRepository(): ShiftPlanRepository
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
	public function getWorktimeService(): WorktimeService
	{
		return new WorktimeService(
			new WorktimeManagerFactory(
				$this->getViolationManager(),
				$this->getViolationRulesRepository(),
				$this->getWorktimeRepository()
			),
			$this->getWorktimeAgentManager(),
			$this->getWorktimeActionList(),
			$this->getWorktimeRepository(),
			$this->getWorktimeNotificationService(),
			$this->getLiveFeedManager()
		);
	}

	public function getLiveFeedManager(): WorktimeLiveFeedManager
	{
		return new WorktimeLiveFeedManager();
	}

	public function getWorktimeRepository(): WorktimeRepository
	{
		if (!static::$worktimeRepository)
		{
			static::$worktimeRepository = new WorktimeRepository();
		}
		return static::$worktimeRepository;
	}

	public function getShiftPlanProvider(): ShiftPlanProvider
	{
		return new ShiftPlanProvider();
	}

	/**
	 * @return CalendarRepository
	 */
	public function getCalendarRepository(): CalendarRepository
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
	public function getWorktimeReportRepository(): WorktimeReportRepository
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
	public function getAbsenceRepository(): AbsenceRepository
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
		return new WorktimeAgentManager($this->getViolationRulesRepository(), $this->getWorktimeRepository(), $this->getShiftPlanProvider(), $this->getScheduleProvider());
	}

	public function getScheduleService()
	{
		return new ScheduleService(
			new CalendarService(new CalendarRepository()),
			new ShiftService($this->getShiftRepository(), $this->getShiftPlanRepository(), $this->getWorktimeAgentManager()),
			$this->getScheduleAssignmentsService(),
			$this->getViolationRulesService(),
			$this->getWorktimeAgentManager(),
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

	public function getWorktimeActionList(): WorktimeActionList
	{
		if (static::$worktimeActionList === null)
		{
			static::$worktimeActionList = new WorktimeActionList(
				$this->getShiftPlanProvider(),
				$this->getWorktimeRepository(),
				$this->getScheduleProvider()
			);
		}
		return static::$worktimeActionList;
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

	public function buildShiftsManager(int $userId, ?ScheduleCollection $activeSchedules = null): ShiftsManager
	{
		if ($activeSchedules === null)
		{
			$activeSchedules = $this->getScheduleProvider()->findSchedulesCollectionByUserId($userId);
		}
		return new ShiftsManager($userId, $activeSchedules, $this->getShiftPlanProvider());
	}

	public function buildWorktimeRecordManager(
		WorktimeRecord $record,
		?Schedule $schedule,
		?Shift $shift,
		?ScheduleCollection $activeSchedules = null
	): WorktimeRecordManager
	{
		return new WorktimeRecordManager(
			$record,
			$schedule,
			$shift,
			$this->getTimeHelper()->getUserDateTimeNow($record->getUserId()),
			$this->buildShiftsManager($record->getUserId(), $activeSchedules)
		);
	}
}