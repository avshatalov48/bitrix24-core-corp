<?php
namespace Bitrix\Timeman\Service\Schedule;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Timeman\Form\Schedule\ScheduleForm;
use Bitrix\Timeman\Helper\EntityCodesHelper;
use Bitrix\Timeman\Helper\Form\Schedule\ScheduleFormHelper;
use Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartment;
use Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartmentTable;
use Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUser;
use Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUserTable;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\ScheduleTable;
use Bitrix\Timeman\Model\Schedule\Shift\ShiftCollection;
use Bitrix\Timeman\Provider\Schedule\ScheduleProvider;
use Bitrix\Timeman\Service\Agent\WorktimeAgentManager;
use Bitrix\Timeman\Service\BaseService;
use Bitrix\Timeman\Service\BaseServiceResult;
use Bitrix\Timeman\Service\Exception\BaseServiceException;
use Bitrix\Timeman\Service\Schedule\Result\ScheduleServiceResult;
use Bitrix\Timeman\Service\Worktime\Result\WorktimeServiceResult;

class ScheduleService extends BaseService
{
	/** @var ShiftService */
	private $shiftService;
	/** @var CalendarService */
	private $calendarService;
	/** @var ScheduleProvider */
	private $scheduleProvider;
	/** @var ScheduleAssignmentsService */
	private $assignmentsService;
	/** @var ViolationRulesService */
	private $violationRulesService;
	private $worktimeAgentManager;

	public function __construct(
		CalendarService $calendarService,
		ShiftService $shiftService,
		ScheduleAssignmentsService $assignmentsService,
		ViolationRulesService $violationRulesService,
		WorktimeAgentManager $worktimeAgentManager,
		ScheduleProvider $scheduleProvider
	)
	{
		$this->assignmentsService = $assignmentsService;
		$this->shiftService = $shiftService;
		$this->calendarService = $calendarService;
		$this->scheduleProvider = $scheduleProvider;
		$this->violationRulesService = $violationRulesService;
		$this->worktimeAgentManager = $worktimeAgentManager;
	}

	/**
	 * @param ScheduleForm $scheduleForm
	 * @return \Bitrix\Timeman\Service\BaseServiceResult|ScheduleServiceResult
	 * @throws \Exception
	 */
	public function add(ScheduleForm $scheduleForm)
	{
		return $this->wrapAction(function () use ($scheduleForm) {

			# calendar
			$calendarResult = $this->safeRun($this->saveCalendar($scheduleForm));

			# schedule
			$schedule = Schedule::create(
				$scheduleForm,
				$calendarResult->getCalendar()->getId()
			);
			$this->safeRun($this->scheduleProvider->save($schedule));

			$schedule->setCalendar($calendarResult->getCalendar());

			# shifts
			if (!$schedule->isFlextime())
			{
				$this->safeRun($this->createShifts($schedule, $scheduleForm));
			}

			# violation rules
			$this->safeRun($violationResult = $this->violationRulesService->add($scheduleForm->violationForm, $schedule));

			# users
			# departments
			$this->safeRun($this->saveAssignments($schedule, $scheduleForm));

			$this->safeRun($this->excludeSelectedDepartmentsFromOtherSchedules($schedule));

			return $this->buildResultWithSchedule($schedule);
		});
	}

	/**
	 * @param int $scheduleId
	 * @param ScheduleForm $scheduleForm
	 * @return BaseServiceResult
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function update($scheduleId, ScheduleForm $scheduleForm)
	{
		$schedule = $this->scheduleProvider->findByIdWith($scheduleId, [
			'CALENDAR',
			'CALENDAR.EXCLUSIONS',
			'SHIFTS',
			'DEPARTMENT_ASSIGNMENTS',
			'USER_ASSIGNMENTS',
			'SCHEDULE_VIOLATION_RULES',
		]);
		if (!$schedule)
		{
			return (new ScheduleServiceResult())->addScheduleNotFoundError();
		}

		$result = $this->wrapAction(function () use ($schedule, $scheduleForm) {

			# schedule
			$wasAutoClosing = $schedule->isAutoClosing();
			$this->safeRun($this->editSchedule($schedule, $scheduleForm));
			$autoClosingAfterUpdate = $schedule->isAutoClosing();

			# violation rules
			$this->safeRun($this->violationRulesService->update($scheduleForm->violationForm, $schedule));
			// todo $schedule->setScheduleViolationRules($violationRules);

			# shifts
			if ($schedule->isFlextime())
			{
				$this->safeRun($this->removeShifts($schedule));
			}
			else
			{
				$this->safeRun($this->removeShifts($schedule, $scheduleForm));
				$this->safeRun($this->createShifts($schedule, $scheduleForm));
				$this->safeRun($updatedEndIdsResult = $this->updateShifts($schedule, $scheduleForm));
				/** @var ShiftCollection $shiftCollection */
				$shiftCollection = $updatedEndIdsResult->getData()[0];
				if ($wasAutoClosing && $autoClosingAfterUpdate && $shiftCollection->count() > 0)
				{
					$this->worktimeAgentManager->deleteAutoClosingAgents($schedule, $shiftCollection);
					$this->worktimeAgentManager->addAutoClosingAgents($schedule, $shiftCollection);
				}
			}

			# auto close records
			if ($wasAutoClosing && !$autoClosingAfterUpdate)
			{
				$this->worktimeAgentManager->deleteAutoClosingAgents($schedule);
			}
			elseif (!$wasAutoClosing && $autoClosingAfterUpdate)
			{
				$this->worktimeAgentManager->addAutoClosingAgents($schedule);
			}

			# users
			# departments
			$this->safeRun($this->saveAssignments($schedule, $scheduleForm));

			# calendar
			$this->safeRun($this->saveCalendar($scheduleForm, $schedule));

			return $this->buildResultWithSchedule($schedule);
		});

		if (!WorktimeServiceResult::isSuccessResult($result))
		{
			return ScheduleServiceResult::createByResult($result);
		}

		$this->wrapAction(function () use ($schedule, $scheduleForm) {
			$this->safeRun($this->excludeSelectedDepartmentsFromOtherSchedules($schedule));
		});
		return $this->buildResultWithSchedule($schedule);
	}

	/**
	 * @param ScheduleForm $scheduleForm
	 * @param Schedule $schedule
	 * @return ScheduleServiceResult
	 */
	private function excludeSelectedDepartmentsFromOtherSchedules($schedule)
	{
		$assignmentsMap = (new ScheduleFormHelper())->calculateSchedulesMapBySchedule($schedule, true);

		foreach ($assignmentsMap as $assignCode => $schedules)
		{
			foreach ($schedules as $schedulesData)
			{
				if (EntityCodesHelper::isDepartment($assignCode)
					&& EntityCodesHelper::getDepartmentId($assignCode) !== $this->scheduleProvider->getDepartmentRepository()->getBaseDepartmentId())
				{
					$this->excludeDepartments($schedulesData['ID'], [EntityCodesHelper::getDepartmentId($assignCode)]);
				}
			}
		}
		if ($schedule->getIsForAllUsers())
		{
			$this->scheduleProvider->updateIsForAllUsers($schedule);
		}
		return new ScheduleServiceResult();
	}

	public function findSchedulesForDepartments($departmentIds, $exceptScheduleId = null)
	{
		return $this->assignmentsService->findSchedulesForDepartments($departmentIds, $exceptScheduleId);
	}

	/**
	 * @param $scheduleId
	 * @return BaseServiceResult
	 * @throws \Exception
	 */
	public function delete($scheduleId)
	{
		$schedule = $this->scheduleProvider->findByIdWith((int)$scheduleId, ['USER_ASSIGNMENTS', 'DEPARTMENTS']);
		if (!$schedule)
		{
			return (new ScheduleServiceResult())->addScheduleNotFoundError();
		}
		return $this->wrapAction(function () use ($schedule) {
			$schedule->markDeleted();
			(ScheduleTable::getEntity())->cleanCache();
			(ScheduleUserTable::getEntity())->cleanCache();
			(ScheduleDepartmentTable::getEntity())->cleanCache();
			$res = $this->safeRun($this->scheduleProvider->save($schedule));
			$this->safeRun($this->shiftService->deleteFutureShiftPlans($schedule));
			$this->violationRulesService->deletePeriodTimeLackAgents($schedule->getId());
			return ScheduleServiceResult::createByResult($res);
		});
	}

	/**
	 * @param Schedule $schedule
	 * @param ScheduleForm $scheduleForm
	 * @return ScheduleServiceResult
	 */
	private function createShifts($schedule, $scheduleForm)
	{
		$scheduleId = $schedule->getId();
		foreach ($scheduleForm->getShiftForms() as $shiftForm)
		{
			if (!$schedule->obtainShiftByPrimary($shiftForm->shiftId))
			{
				$result = $this->safeRun(
					$this->shiftService->add($scheduleId, $shiftForm)
				);
				$schedule->addToShifts($result->getShift());
			}
		}
		return new ScheduleServiceResult();
	}

	/**
	 * @param Schedule $schedule
	 * @param $scheduleForm
	 * @return \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\Result|\Bitrix\Main\ORM\Data\UpdateResult
	 */
	private function editSchedule($schedule, $scheduleForm)
	{
		// because of uncontrolled cascade saving
		$scheduleToUpdate = Schedule::wakeUp($schedule->collectRawValues());
		foreach ([$schedule, $scheduleToUpdate] as $updatingSchedule)
		{
			/** @var Schedule $updatingSchedule */
			$updatingSchedule->edit(
				$scheduleForm
			);
		}

		return $this->scheduleProvider->save($scheduleToUpdate);
	}

	/**
	 * @param Schedule $schedule
	 * @param ScheduleForm|null $scheduleForm
	 * @return Result
	 * @throws BaseServiceException
	 */
	private function removeShifts(Schedule $schedule, $scheduleForm = null)
	{
		$existedShiftIds = $schedule->getShifts()->getIdList();
		$removedShiftIds = $existedShiftIds;
		if ($scheduleForm)
		{
			$removedShiftIds = array_diff($existedShiftIds, $scheduleForm->getShiftIds());
		}
		if (empty($existedShiftIds) || empty($removedShiftIds))
		{
			return new Result();
		}
		foreach ($removedShiftIds as $removedShiftId)
		{
			$this->safeRun($this->shiftService->deleteShiftById(
				$schedule->getShifts()->getByPrimary($removedShiftId),
				$schedule
			));
			$schedule->removeFromShifts($schedule->obtainShiftByPrimary($removedShiftId));
		}
		return new Result();
	}

	private function updateShifts(Schedule $schedule, ScheduleForm $scheduleForm)
	{
		$result = new Result();
		$changedEndShifts = new ShiftCollection();
		foreach ($scheduleForm->getShiftForms() as $shiftForm)
		{
			if ($shift = $schedule->obtainShiftByPrimary($shiftForm->shiftId))
			{
				$oldValue = $shift->getWorkTimeEnd();
				$this->safeRun($this->shiftService->update($shift, $shiftForm));
				$newValue = $shift->getWorkTimeEnd();
				if ($oldValue !== $newValue)
				{
					$changedEndShifts->add($shift);
				}
			}
		}
		$result->setData([$changedEndShifts]);
		return $result;
	}

	private function saveCalendar(ScheduleForm $scheduleForm, Schedule $schedule = null)
	{
		if (!$scheduleForm->calendarForm->calendarId)
		{
			return $this->calendarService->add($scheduleForm->calendarForm);
		}

		if ($scheduleForm->calendarForm->calendarId != $schedule->getCalendarId())
		{
			return (new ScheduleServiceResult())->addError(new Error('Calendar Id should match the Calendar Id saved in schedule'));
		}

		$calendarRes = $this->calendarService->update($schedule->getCalendar(), $scheduleForm->calendarForm);
		if (WorktimeServiceResult::isSuccessResult($calendarRes))
		{
			$schedule->setCalendar($calendarRes->getCalendar());
		}
		return $calendarRes;
	}

	/**
	 * @param Schedule $schedule
	 * @return ScheduleServiceResult
	 */
	private function buildResultWithSchedule($schedule)
	{
		$schedule->defineUsersCount(
			$this->scheduleProvider->getUsersCount($schedule)
		);
		return (new ScheduleServiceResult())
			->setSchedule($schedule);
	}

	# delegation, for using one public interface only

	/**
	 * @param Schedule $schedule
	 * @param ScheduleForm $scheduleForm
	 * @return ScheduleServiceResult
	 */
	private function saveAssignments($schedule, ScheduleForm $scheduleForm)
	{
		$res = $this->assignmentsService->saveAssignments($schedule, $scheduleForm);
		if (!$res->isSuccess())
		{
			return $res;
		}
		$schedule->removeAllDepartmentAssignments();
		$schedule->removeAllUserAssignments();
		foreach ($scheduleForm->departmentIds as $departmentId)
		{
			$schedule->addToDepartmentAssignments(
				(new ScheduleDepartment(false))
					->setDepartmentId($departmentId)
					->setIsIncluded()
			);
		}
		foreach ($scheduleForm->departmentIdsExcluded as $departmentIdExc)
		{
			$schedule->addToDepartmentAssignments(
				(new ScheduleDepartment(false))
					->setDepartmentId($departmentIdExc)
					->setIsExcluded()
			);
		}
		foreach ($scheduleForm->userIds as $userId)
		{
			$schedule->addToUserAssignments(ScheduleUser::create($schedule->getId(), $userId));
		}
		foreach ($scheduleForm->userIdsExcluded as $userIdExc)
		{
			$schedule->addToUserAssignments(ScheduleUser::create($schedule->getId(), $userIdExc, true));
		}
		return $res;
	}

	public function addUserAssignments($scheduleId, $userIds)
	{
		return $this->assignmentsService->addUserAssignment($scheduleId, $userIds);
	}

	/**
	 * @param $scheduleId
	 * @param $userIds
	 * @return BaseServiceResult
	 */
	public function deleteUserAssignments($scheduleId, $userIds)
	{
		$result = $this->assignmentsService->deleteUserAssignments($scheduleId, $userIds);
		if ($result->isSuccess())
		{
			return $this->shiftService->deleteFutureShiftPlans($result->getSchedule(), null, $userIds);
		}
		return $result;
	}

	private function excludeDepartments($scheduleId, $depIds)
	{
		return $this->assignmentsService->excludeDepartments($scheduleId, $depIds);
	}
}