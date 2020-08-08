<?php
namespace Bitrix\Timeman\Service\Worktime\Violation;

use Bitrix\Main\EO_User_Collection;
use Bitrix\Main\Error;
use Bitrix\Timeman\Helper\EntityCodesHelper;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\Shift\Shift;
use Bitrix\Timeman\Model\Schedule\Violation\ViolationRules;
use Bitrix\Timeman\Model\User\UserCollection;
use Bitrix\Timeman\Provider\Schedule\ScheduleProvider;
use Bitrix\Timeman\Repository\AbsenceRepository;
use Bitrix\Timeman\Repository\DepartmentRepository;
use Bitrix\Timeman\Repository\Schedule\CalendarRepository;
use Bitrix\Timeman\Repository\Worktime\WorktimeRepository;

class FixedScheduleViolationBuilder extends WorktimeViolationBuilder
{
	/** @var WorktimeRepository */
	private $worktimeRepository;
	/** @var DepartmentRepository */
	private $departmentRepository;

	public function __construct(
		WorktimeViolationParams $params,
		CalendarRepository $calendarRepository,
		ScheduleProvider $scheduleProvider,
		AbsenceRepository $absenceRepository,
		WorktimeRepository $worktimeRepository,
		DepartmentRepository $departmentRepository
	)
	{
		parent::__construct($params, $calendarRepository, $scheduleProvider, $absenceRepository);
		$this->worktimeRepository = $worktimeRepository;
		$this->departmentRepository = $departmentRepository;
	}

	/**
	 * @param WorktimeViolationParams $params
	 * @param \DateTime $fromDateTime
	 * @param \DateTime $toDateTime
	 * @return WorktimeViolationResult
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function buildPeriodTimeLackViolation($params, $fromDateTime, $toDateTime)
	{
		$result = new WorktimeViolationResult();
		$schedule = $params->getSchedule();
		$violationRules = $this->getViolationRules();
		if (!$violationRules->isPeriodWorkTimeLackControlEnabled())
		{
			return $result->addError(new Error('', WorktimeViolationResult::ERROR_CODE_VIOLATION_NOT_UNDER_CONTROL));
		}
		$shifts = $schedule->obtainShifts();
		if (empty($shifts))
		{
			return $result->addError(new Error('', WorktimeViolationResult::ERROR_CODE_SHIFT_NOT_FOUND));
		}

		$activeUsers = $this->findActiveUsers($schedule, $violationRules->getEntityCode());
		if ($activeUsers->count() === 0)
		{
			return $result->addError(new Error('', WorktimeViolationResult::ERROR_CODE_NO_USERS_ASSIGNED_TO_SCHEDULE));
		}


		$workDays = [];
		foreach ($shifts as $shift)
		{
			foreach (str_split($shift->getWorkDays()) as $workDay)
			{
				if (isset($workDays[$workDay]))
				{
					return $result->addError(new Error('', WorktimeViolationResult::ERROR_CODE_SHIFTS_DAYS_INTERSECT));
				}
				$workDays[$workDay] = $shift->getDuration() - $shift->getBreakDuration();
				if ($workDays[$workDay] < 0)
				{
					return $result->addError(new Error('', WorktimeViolationResult::ERROR_CODE_INVALID_SHIFT_DURATION));
				}
			}
		}

		$this->setAbsenceData($this->findAbsenceForPeriod($fromDateTime, $toDateTime, $activeUsers->getIdList()));

		$workedStatistics = $this->findRecordsForPeriod($fromDateTime, $toDateTime, $schedule, $activeUsers->getIdList());
		$workedStatistics = array_combine(array_column($workedStatistics, 'USER_ID'), $workedStatistics);

		$periodDates = $this->createPeriodDates($fromDateTime, $toDateTime);
		foreach ($activeUsers as $user)
		{
			$expectedSeconds = $this->calculateExpectedWorkedSecondsForPeriod(
				$periodDates,
				$workDays,
				$user->getId()
			);
			if ($expectedSeconds <= 0)
			{
				continue;
			}

			if (empty($workedStatistics[$user->getId()]['DURATION'])
				|| $expectedSeconds - (int)$workedStatistics[$user->getId()]['DURATION'] > $violationRules->getMaxWorkTimeLackForPeriod())
			{
				if (empty($workedStatistics[$user->getId()]))
				{
					$recordSeconds = 0;
					$violatedSeconds = $expectedSeconds;
				}
				else
				{
					$recordSeconds = $workedStatistics[$user->getId()]['DURATION'];
					$violatedSeconds = $expectedSeconds - (int)$workedStatistics[$user->getId()]['DURATION'] - $violationRules->getMaxWorkTimeLackForPeriod();
				}
				$result->addViolation(
					$this->createViolation(
						WorktimeViolation::TYPE_TIME_LACK_FOR_PERIOD,
						$recordSeconds,
						$violatedSeconds,
						$user->getId()
					)
				);
			}
		}

		return $result;
	}

	/**
	 * @param \DateTime[] $checkingDates
	 * @param Schedule $schedule
	 * @return int
	 */
	private function calculateExpectedWorkedSecondsForPeriod($checkingDates, $workDays, $userId)
	{
		$seconds = 0;

		foreach ($checkingDates as $checkingDateTime)
		{
			if (isset($workDays[$checkingDateTime->format('N')])
				&& !$this->isDateTimeHoliday($checkingDateTime)
				&& !$this->isUserWasAbsent($userId, $checkingDateTime)
			)
			{
				$seconds += $workDays[$checkingDateTime->format('N')];
			}
		}
		return $seconds;
	}

	private function createPeriodDates($fromDateTime, $toDateTime)
	{
		$step = new \DateInterval('P1D');
		$dateMin = clone $fromDateTime;
		do
		{
			$checkingDates[] = clone $dateMin;
			$dateMin->add($step);
		}
		while ($dateMin <= $toDateTime);
		return $checkingDates;
	}

	private function isRecordStartedOnHoliday()
	{
		$startDateTime = $this->buildRecordStartDateTime();
		return $this->isDateTimeHoliday($startDateTime);
	}

	private function isDateTimeHoliday(\DateTime $dateTime)
	{
		$calendar = $this->getCalendar($this->getSchedule()->getCalendarId(), $dateTime->format('Y'));
		if ($calendar && !empty($calendar->obtainFinalExclusions()[$dateTime->format('Y')]))
		{
			return isset($calendar->obtainFinalExclusions()[$dateTime->format('Y')][$dateTime->format('n')][$dateTime->format('j')]);
		}
		return false;
	}

	protected function skipViolationsCheck()
	{
		if (parent::skipViolationsCheck() || $this->isRecordStartedOnHoliday())
		{
			return true;
		}
		if (!$this->getShift())
		{
			// assume that shift is empty because user works outside of working days of shift
			return true;
		}
		// if record has shift - check violations only if it is a working day based on shift configuration
		return !Shift::isDateInShiftWorkDays($this->buildRecordStartDateTime(), $this->getShift());
	}

	protected function buildStartViolations()
	{
		$record = $this->getRecord();
		$violations = [];
		$recordedStartSeconds = $this->getTimeHelper()->convertUtcTimestampToDaySeconds(
			$record->getRecordedStartTimestamp(),
			$record->getStartOffset()
		);

		$violations = array_merge($violations, $this->buildOffsetStartViolations($recordedStartSeconds));
		$violations = array_merge($violations, $this->buildExactStartViolations($recordedStartSeconds));
		$violations = array_merge($violations, $this->buildRelativeStartViolations($recordedStartSeconds));

		return $violations;
	}

	protected function buildEndViolations()
	{
		$record = $this->getRecord();
		if (!$this->issetProperty($record->getRecordedStopTimestamp()))
		{
			return [];
		}
		$violations = [];
		$recordedStopSeconds = $this->getTimeHelper()->convertUtcTimestampToDaySeconds(
			$record->getRecordedStopTimestamp(),
			$record->getStopOffset()
		);
		$violations = array_merge($violations, $this->buildOffsetStopViolations($recordedStopSeconds));
		$violations = array_merge($violations, $this->buildExactStopViolations($recordedStopSeconds));
		$violations = array_merge($violations, $this->buildRelativeStopViolations($recordedStopSeconds));

		return $violations;
	}

	protected function buildDurationViolations()
	{
		$record = $this->getRecord();
		if (!$this->issetProperty($record->getRecordedDuration()))
		{
			return [];
		}
		$violationsConfig = $this->getViolationRules();
		if (!ViolationRules::isViolationConfigured($violationsConfig['MIN_DAY_DURATION']))
		{
			return [];
		}

		if ($record->getRecordedDuration() < $violationsConfig['MIN_DAY_DURATION'])
		{
			return [
				$this->createViolation(
					WorktimeViolation::TYPE_MIN_DAY_DURATION,
					$record->getRecordedDuration(),
					$record->getRecordedDuration() - $violationsConfig['MIN_DAY_DURATION']
				),
			];
		}
		return [];
	}

	private function buildOffsetStartViolations($recordedStartSeconds)
	{
		$maxOffsetStart = $this->getViolationRules()['MAX_OFFSET_START'];
		if (!$this->getShift() || !ViolationRules::isViolationConfigured($maxOffsetStart))
		{
			return [];
		}

		$violations = [];
		if ($recordedStartSeconds > $this->getShift()->getWorkTimeStart() + $maxOffsetStart)
		{
			$violations[] = $this->createViolation(
				WorktimeViolation::TYPE_LATE_START,
				$this->getRecord()->getRecordedStartTimestamp(),
				$recordedStartSeconds - $this->getShift()->getWorkTimeStart() - $maxOffsetStart
			);
		}

		return $violations;
	}

	private function buildExactStartViolations($recordedStartSeconds)
	{
		$violations = [];
		$violationsConfig = $this->getViolationRules();
		$maxExactStart = $violationsConfig->getMaxExactStart();
		if (!ViolationRules::isViolationConfigured($maxExactStart))
		{
			return [];
		}

		if ($recordedStartSeconds > $maxExactStart)
		{
			$violations[] = $this->createViolation(
				WorktimeViolation::TYPE_LATE_START,
				$this->getRecord()->getRecordedStartTimestamp(),
				$recordedStartSeconds - $maxExactStart
			);
		}

		return $violations;
	}

	private function buildRelativeStartViolations($recordedStartSeconds)
	{
		$violations = [];
		$violationsConfig = $this->getViolationRules();
		$startFrom = $violationsConfig->getRelativeStartFrom();
		if (ViolationRules::isViolationConfigured($startFrom))
		{
			if ($recordedStartSeconds < $startFrom)
			{
				$violations[] = $this->createViolation(
					WorktimeViolation::TYPE_EARLY_START,
					$this->getRecord()->getRecordedStartTimestamp(),
					$recordedStartSeconds - $startFrom
				);
			}
		}

		$startTo = $violationsConfig->getRelativeStartTo();
		if (ViolationRules::isViolationConfigured($startTo))
		{
			if ($recordedStartSeconds > $startTo)
			{
				$violations[] = $this->createViolation(
					WorktimeViolation::TYPE_LATE_START,
					$this->getRecord()->getRecordedStartTimestamp(),
					$recordedStartSeconds - $startTo
				);
			}
		}
		return $violations;
	}

	private function buildOffsetStopViolations($recordedStopSeconds)
	{
		$minOffsetEnd = $this->getViolationRules()->getMinOffsetEnd();
		if (!$this->getShift() || !ViolationRules::isViolationConfigured($minOffsetEnd))
		{
			return [];
		}

		$violations = [];

		if ($recordedStopSeconds < $this->getShift()->getWorkTimeEnd() - $minOffsetEnd)
		{
			$violations[] = $this->createViolation(
				WorktimeViolation::TYPE_EARLY_ENDING,
				$this->getRecord()->getRecordedStopTimestamp(),
				$recordedStopSeconds - $this->getShift()->getWorkTimeEnd() + $minOffsetEnd
			);
		}

		return $violations;
	}

	private function buildExactStopViolations($recordedStopSeconds)
	{
		$violations = [];
		$violationsConfig = $this->getViolationRules();
		$minExactEnd = $violationsConfig->getMinExactEnd();
		if (!ViolationRules::isViolationConfigured($minExactEnd))
		{
			return [];
		}

		if ($recordedStopSeconds < $minExactEnd)
		{
			$violations[] = $this->createViolation(
				WorktimeViolation::TYPE_EARLY_ENDING,
				$this->getRecord()->getRecordedStopTimestamp(),
				$recordedStopSeconds - $minExactEnd
			);
		}

		return $violations;
	}

	private function buildRelativeStopViolations($recordedStopSeconds)
	{
		$violations = [];
		$violationsConfig = $this->getViolationRules();
		$stopFrom = $violationsConfig->getRelativeEndFrom();
		if (ViolationRules::isViolationConfigured($stopFrom))
		{
			if ($recordedStopSeconds < $stopFrom)
			{
				$violations[] = $this->createViolation(
					WorktimeViolation::TYPE_EARLY_ENDING,
					$this->getRecord()->getRecordedStopTimestamp(),
					$recordedStopSeconds - $stopFrom
				);
			}
		}

		$stopTo = $violationsConfig->getRelativeEndTo();
		if (ViolationRules::isViolationConfigured($stopTo))
		{
			if ($recordedStopSeconds > $stopTo)
			{
				$violations[] = $this->createViolation(
					WorktimeViolation::TYPE_LATE_ENDING,
					$this->getRecord()->getRecordedStopTimestamp(),
					$recordedStopSeconds - $stopTo
				);
			}
		}
		return $violations;
	}

	/**
	 * @param Schedule $schedule
	 * @param $checkingEntityCode
	 * @return UserCollection
	 */
	protected function findActiveUsers(Schedule $schedule, $checkingEntityCode)
	{
		/** @var UserCollection $userCollection */
		$userCollection = new UserCollection();
		if (EntityCodesHelper::getAllUsersCode() === $checkingEntityCode)
		{
			return $this->scheduleProvider->findActiveUsers($schedule);
		}
		$users = $this->scheduleProvider->findActiveScheduleUserIds($schedule);

		if (EntityCodesHelper::isUser($checkingEntityCode))
		{
			if (in_array(EntityCodesHelper::getUserId($checkingEntityCode), $users, true))
			{
				$user = $this->scheduleProvider->getUsersBaseQuery()
					->where('ID', EntityCodesHelper::getUserId($checkingEntityCode))
					->exec()
					->fetchObject();
				if ($user)
				{
					$userCollection->add($user);
				}
			}
		}
		elseif (EntityCodesHelper::isDepartment($checkingEntityCode))
		{
			$includedUsers = [];
			$depId = EntityCodesHelper::getDepartmentId($checkingEntityCode);
			$departmentsIds = $this->departmentRepository->getAllChildDepartmentsIds($depId);
			$departmentsIds[] = [$depId];
			foreach ($departmentsIds as $departmentId)
			{
				$depUserIds = $this->departmentRepository->getUsersOfDepartment($departmentId);
				foreach ($userCollection->getAll() as $user)
				{
					if (in_array($user->getId(), $depUserIds, true))
					{
						$includedUsers[$user->getId()] = true;
					}
				}
			}
			$includedUsers = array_keys($includedUsers);
			foreach ($userCollection->getAll() as $user)
			{
				if (!in_array($user->getId(), $includedUsers, true))
				{
					$userCollection->removeByPrimary($user->getId());
				}
			}
		}
		return $userCollection;
	}

	/**
	 * @param \DateTime $fromDateTime
	 * @param \DateTime $toDateTime
	 * @param $userIds
	 * @return array
	 */
	protected function findAbsenceForPeriod($fromDateTime, $toDateTime, $userIds)
	{
		return $this->absenceRepository
			->findAbsences(
				convertTimeStamp($fromDateTime->getTimestamp(), 'FULL'),
				convertTimeStamp($toDateTime->getTimestamp(), 'FULL'),
				$userIds
			);
	}

	protected function findRecordsForPeriod(\DateTime $fromDateTime, \DateTime $toDateTime, Schedule $schedule, $userIds)
	{
		return $this->worktimeRepository
			->findAllForPeriod($fromDateTime, $toDateTime, $schedule, $userIds);
	}
}