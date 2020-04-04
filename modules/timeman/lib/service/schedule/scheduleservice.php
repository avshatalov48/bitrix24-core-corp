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
use Bitrix\Timeman\Repository\Schedule\ScheduleRepository;
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
	/** @var ScheduleRepository */
	private $scheduleRepository;
	/** @var ScheduleAssignmentsService */
	private $assignmentsService;
	/** @var ViolationRulesService */
	private $violationRulesService;

	public function __construct(
		CalendarService $calendarService,
		ShiftService $shiftService,
		ScheduleAssignmentsService $assignmentsService,
		ViolationRulesService $violationRulesService,
		ScheduleRepository $scheduleRepository
	)
	{
		$this->assignmentsService = $assignmentsService;
		$this->shiftService = $shiftService;
		$this->calendarService = $calendarService;
		$this->scheduleRepository = $scheduleRepository;
		$this->violationRulesService = $violationRulesService;
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
			$this->safeRun($this->scheduleRepository->save($schedule));

			$schedule->setCalendar($calendarResult->getCalendar());

			# shifts
			if (!$schedule->isFlexible())
			{
				$this->safeRun($this->createShifts($schedule, $scheduleForm));
			}

			# violation rules
			$this->safeRun($violationResult = $this->violationRulesService->add($scheduleForm->violationForm, $schedule));

			# users
			# departments
			$this->safeRun($this->saveAssignments($schedule, $scheduleForm));

			$this->safeRun($this->excludeSelectedDepartmentsFromOtherSchedules($scheduleForm, $schedule));

			return $this->buildResultWithSchedule($schedule);
		});
	}

	/**
	 * @param $scheduleOrId
	 * @param ScheduleForm $scheduleForm
	 * @return BaseServiceResult
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function update($scheduleOrId, ScheduleForm $scheduleForm)
	{
		$schedule = $scheduleOrId;
		if (!($schedule instanceof Schedule))
		{
			$schedule = $this->scheduleRepository->findByIdWith($scheduleOrId, [
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
		}

		$result = $this->wrapAction(function () use ($schedule, $scheduleForm) {

			# schedule
			$this->safeRun($this->editSchedule($schedule, $scheduleForm));

			# violation rules
			$this->safeRun($this->violationRulesService->update($scheduleForm->violationForm, $schedule));
			// todo $schedule->setScheduleViolationRules($violationRules);

			# shifts
			if ($schedule->isFlexible())
			{
				$this->safeRun($this->removeShifts($schedule));
			}
			else
			{
				$this->safeRun($this->removeShifts($schedule, $scheduleForm));
				$this->safeRun($this->createShifts($schedule, $scheduleForm));
				$this->safeRun($this->updateShifts($schedule, $scheduleForm));
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
			$this->safeRun(
				$this->deleteShiftPlansOfNotActiveUsers($schedule->getId())
			);
			$this->safeRun($this->excludeSelectedDepartmentsFromOtherSchedules($scheduleForm, $schedule));

		});
		return $this->buildResultWithSchedule($schedule);
	}

	/**
	 * @param ScheduleForm $scheduleForm
	 * @param Schedule $schedule
	 * @return ScheduleServiceResult
	 */
	private function excludeSelectedDepartmentsFromOtherSchedules($scheduleForm, $schedule)
	{
		$codes = [];
		if ($schedule->getIsForAllUsers())
		{
			$codes[] = EntityCodesHelper::getAllUsersCode();
		}
		$assignmentsMap = (new ScheduleFormHelper())
			->calculateScheduleAssignmentsMap(array_merge($codes, EntityCodesHelper::buildDepartmentCodes($scheduleForm->departmentIds)), $schedule);

		foreach ($assignmentsMap as $assignCode => $schedules)
		{
			foreach ($schedules as $schedulesData)
			{
				if (EntityCodesHelper::getAllUsersCode() === $assignCode)
				{
					$this->scheduleRepository->save(
						Schedule::wakeUp(['ID' => $schedulesData['ID']])->setIsForAllUsers(false)
					);
				}
				elseif (EntityCodesHelper::isDepartment($assignCode)
						&& EntityCodesHelper::getDepartmentId($assignCode) !== $this->scheduleRepository->getDepartmentRepository()->getBaseDepartmentId())
				{
					$this->excludeDepartments($schedulesData['ID'], [EntityCodesHelper::getDepartmentId($assignCode)]);
				}
			}
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
		$schedule = $this->scheduleRepository->findById((int)$scheduleId);
		if (!$schedule)
		{
			return (new ScheduleServiceResult())->addScheduleNotFoundError();
		}
		return $this->wrapAction(function () use ($schedule) {
			$schedule->markDeleted();
			$res = $this->safeRun($this->scheduleRepository->save($schedule));

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

	private function editSchedule($schedule, $scheduleForm)
	{
		// todo-annabo because of uncontrolled cascade saving
		$scheduleToUpdate = $this->scheduleRepository->findById($schedule->getId());
		foreach ([$schedule, $scheduleToUpdate] as $updatingSchedule)
		{
			/** @var Schedule $updatingSchedule */
			$updatingSchedule->edit(
				$scheduleForm
			);
		}

		return $this->scheduleRepository->save($scheduleToUpdate);
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
			$this->safeRun($this->shiftService->deleteShiftById($removedShiftId));
			$schedule->removeFromShifts($schedule->obtainShiftByPrimary($removedShiftId));
		}
		return new Result();
	}

	private function updateShifts(Schedule $schedule, ScheduleForm $scheduleForm)
	{
		foreach ($scheduleForm->getShiftForms() as $shiftForm)
		{
			if ($shift = $schedule->obtainShiftByPrimary($shiftForm->shiftId))
			{
				$this->safeRun($this->shiftService->update($shift, $shiftForm));
			}
		}
		return new Result();
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
			$this->scheduleRepository->getUsersCount($schedule->getId(), $schedule->obtainDepartmentAssignments())
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
					->setStatus(ScheduleDepartmentTable::INCLUDED)
			);
		}
		foreach ($scheduleForm->departmentIdsExcluded as $departmentIdExc)
		{
			$schedule->addToDepartmentAssignments(
				(new ScheduleDepartment(false))
					->setDepartmentId($departmentIdExc)
					->setStatus(ScheduleDepartmentTable::EXCLUDED)
			);
		}
		foreach ($scheduleForm->userIds as $userId)
		{
			$schedule->addToUserAssignments(
				(new ScheduleUser(false))
					->setUserId($userId)
					->setStatus(ScheduleUserTable::INCLUDED)
			);
		}
		foreach ($scheduleForm->userIdsExcluded as $userIdExc)
		{
			$schedule->addToUserAssignments(
				(new ScheduleUser(false))
					->setUserId($userIdExc)
					->setStatus(ScheduleUserTable::EXCLUDED)
			);
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
		return $this->assignmentsService->deleteUserAssignments($scheduleId, $userIds);
	}

	private function excludeDepartments($scheduleId, $depIds)
	{
		return $this->assignmentsService->excludeDepartments($scheduleId, $depIds);
	}

	/**
	 * @param $scheduleIdOrSchedule
	 * @return ScheduleServiceResult
	 */
	private function deleteShiftPlansOfNotActiveUsers($scheduleIdOrSchedule)
	{
		return $this->assignmentsService->deleteShiftPlansOfNotActiveUsers($scheduleIdOrSchedule);
	}
}