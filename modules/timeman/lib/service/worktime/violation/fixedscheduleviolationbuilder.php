<?php
namespace Bitrix\Timeman\Service\Worktime\Violation;

use Bitrix\Main\Error;
use Bitrix\Timeman\Helper\EntityCodesHelper;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\Shift\Shift;
use Bitrix\Timeman\Model\Schedule\Violation\ViolationRules;

class FixedScheduleViolationBuilder extends WorktimeViolationBuilder
{
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
		if ($violationRules->getEntityCode() === EntityCodesHelper::getAllUsersCode())
		{
			$activeUsers = $this->findActiveUsers($schedule);
		}
		else
		{
			if (EntityCodesHelper::isUser($violationRules->getEntityCode()))
			{
				$userIds = [EntityCodesHelper::getUserId($violationRules->getEntityCode())];
			}
			elseif (EntityCodesHelper::isDepartment($violationRules->getEntityCode()))
			{
				$departmentId = EntityCodesHelper::getDepartmentId($violationRules->getEntityCode());
				$userIds = $this->scheduleRepository->getDepartmentRepository()
					->getUsersOfDepartment($departmentId);
			}
			if (!empty($userIds))
			{
				$req = $this->scheduleRepository
					->buildActiveScheduleUsersQuery(
						$schedule->getId(),
						$schedule->obtainDepartmentAssignments(),
						['USER_IDS' => $userIds]
					);
				$activeUsers = $req->exec()->fetchAll();
			}
		}

		if (empty($activeUsers))
		{
			return $result->addError(new Error('', WorktimeViolationResult::ERROR_CODE_NO_USERS_ASSIGNED_TO_SCHEDULE));
		}

		$this->setAbsenceData($this->findAbsenceForPeriod($fromDateTime, $toDateTime, array_column($activeUsers, 'ID')));

		$workedStatistics = $this->findRecordsForPeriod($fromDateTime, $toDateTime, $schedule, array_column($activeUsers, 'ID'));
		$workedStatistics = array_combine(array_column($workedStatistics, 'USER_ID'), $workedStatistics);

		$periodDates = $this->createPeriodDates($fromDateTime, $toDateTime);
		foreach ($activeUsers as $user)
		{
			$expectedSeconds = $this->calculateExpectedWorkedSecondsForPeriod(
				$periodDates,
				$workDays,
				$user['ID']
			);
			if ($expectedSeconds <= 0)
			{
				continue;
			}

			if (empty($workedStatistics[$user['ID']]['DURATION'])
				|| $expectedSeconds - (int)$workedStatistics[$user['ID']]['DURATION'] > $violationRules->getMaxWorkTimeLackForPeriod())
			{
				if (empty($workedStatistics[$user['ID']]))
				{
					$recordSeconds = 0;
					$violatedSeconds = $expectedSeconds;
				}
				else
				{
					$recordSeconds = $workedStatistics[$user['ID']]['DURATION'];
					$violatedSeconds = $expectedSeconds - (int)$workedStatistics[$user['ID']]['DURATION'] - $violationRules->getMaxWorkTimeLackForPeriod();
				}
				$result->addViolation(
					$this->createViolation(
						WorktimeViolation::TYPE_TIME_LACK_FOR_PERIOD,
						$recordSeconds,
						$violatedSeconds,
						$user['ID']
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

	protected function skipViolationsCheck()
	{
		if (parent::skipViolationsCheck())
		{
			return true;
		}
		if (!$this->getShift())
		{
			// assume that shift is empty because user works outside of working days of shift
			return true;
		}
		// if record has shift - check violations only if it is a working day based on shift configuration
		return !Shift::isDateInShiftWorkDays($this->getRecordStartDateTime(), $this->getShift());
	}

	protected function buildStartViolations()
	{
		$record = $this->getRecord();
		$violations = [];
		$recordedStartSeconds = $this->getTimeHelper()->convertUtcTimestampToDaySeconds(
			$record['RECORDED_START_TIMESTAMP'],
			$record['START_OFFSET']
		);

		$violations = array_merge($violations, $this->buildOffsetStartViolations($recordedStartSeconds));
		$violations = array_merge($violations, $this->buildExactStartViolations($recordedStartSeconds));
		$violations = array_merge($violations, $this->buildRelativeStartViolations($recordedStartSeconds));

		return $violations;
	}

	protected function buildEndViolations()
	{
		$record = $this->getRecord();
		if (!$this->issetProperty($record['RECORDED_STOP_TIMESTAMP']))
		{
			return [];
		}
		$violations = [];
		$recordedStopSeconds = $this->getTimeHelper()->convertUtcTimestampToDaySeconds(
			$record['RECORDED_STOP_TIMESTAMP'],
			$record['STOP_OFFSET']
		);
		$violations = array_merge($violations, $this->buildOffsetStopViolations($recordedStopSeconds));
		$violations = array_merge($violations, $this->buildExactStopViolations($recordedStopSeconds));
		$violations = array_merge($violations, $this->buildRelativeStopViolations($recordedStopSeconds));

		return $violations;
	}

	protected function buildDurationViolations()
	{
		$record = $this->getRecord();
		if (!$this->issetProperty($record['RECORDED_DURATION']))
		{
			return [];
		}
		$violationsConfig = $this->getViolationRules();
		if (!ViolationRules::isViolationConfigured($violationsConfig['MIN_DAY_DURATION']))
		{
			return [];
		}

		if ($record['RECORDED_DURATION'] < $violationsConfig['MIN_DAY_DURATION'])
		{
			return [
				$this->createViolation(
					WorktimeViolation::TYPE_MIN_DAY_DURATION,
					$record['RECORDED_DURATION'],
					$record['RECORDED_DURATION'] - $violationsConfig['MIN_DAY_DURATION']
				),
			];
		}
		return [];
	}

	public function buildEditedWorktimeWarnings($checkAllowedDelta)
	{
		return $this->buildEditViolations($checkAllowedDelta);
	}

	protected function buildEditViolations($checkAllowedDelta = true)
	{
		$violationsConfig = $this->getViolationRules();
		if ($checkAllowedDelta && !ViolationRules::isViolationConfigured($violationsConfig['MAX_ALLOWED_TO_EDIT_WORK_TIME']))
		{
			return [];
		}
		$violations = [];
		if (!Schedule::isAutoStartingEnabledForSchedule($this->getSchedule()))
		{
			$violations = array_merge($violations, $this->buildEditStartViolations($checkAllowedDelta));
		}
		if (!Schedule::isAutoClosingEnabledForSchedule($this->getSchedule()))
		{
			$violations = array_merge($violations, $this->buildEditStopViolations($checkAllowedDelta));
		}
		return array_merge(
			$violations,
			$this->buildEditBreakLengthViolations($checkAllowedDelta)
		);
	}

	private function buildEditStartViolations($checkAllowedDelta = true)
	{
		$record = $this->getRecord();

		if (!($this->issetProperty($record['RECORDED_START_TIMESTAMP']) &&
			  $this->issetProperty($record['ACTUAL_START_TIMESTAMP']))
		)
		{
			return [];
		}
		$allowedDiff = 0;
		if ($checkAllowedDelta)
		{
			$allowedDiff = $this->getViolationRules()['MAX_ALLOWED_TO_EDIT_WORK_TIME'];
		}
		if (abs($record['ACTUAL_START_TIMESTAMP'] - $record['RECORDED_START_TIMESTAMP']) > $allowedDiff)
		{
			return [
				$this->createViolation(
					WorktimeViolation::TYPE_EDITED_START,
					$this->getTimeHelper()->convertUtcTimestampToDaySeconds(
						$record['RECORDED_START_TIMESTAMP'],
						$record['START_OFFSET']
					),
					$record['RECORDED_START_TIMESTAMP'] - $record['ACTUAL_START_TIMESTAMP']
				),
			];
		}
		return [];
	}

	private function buildEditBreakLengthViolations($checkAllowedDelta = true)
	{
		$record = $this->getRecord();
		$allowedDiff = 0;
		if ($checkAllowedDelta)
		{
			$allowedDiff = $this->getViolationRules()['MAX_ALLOWED_TO_EDIT_WORK_TIME'];
		}
		if (abs($record['ACTUAL_BREAK_LENGTH'] - $record['TIME_LEAKS']) > $allowedDiff)
		{
			return [
				$this->createViolation(
					WorktimeViolation::TYPE_EDITED_BREAK_LENGTH,
					$record['TIME_LEAKS'],
					$record['TIME_LEAKS'] - $record['ACTUAL_BREAK_LENGTH']
				),
			];
		}
		return [];
	}

	private function buildEditStopViolations($checkAllowedDelta = true)
	{
		$record = $this->getRecord();

		if (!($this->issetProperty($record['RECORDED_STOP_TIMESTAMP']) &&
			  $this->issetProperty($record['ACTUAL_STOP_TIMESTAMP']))
		)
		{
			return [];
		}
		$allowedDiff = 0;
		if ($checkAllowedDelta)
		{
			$allowedDiff = $this->getViolationRules()['MAX_ALLOWED_TO_EDIT_WORK_TIME'];
		}
		if (abs($record['RECORDED_STOP_TIMESTAMP'] - $record['ACTUAL_STOP_TIMESTAMP']) > $allowedDiff)
		{
			return [
				$this->createViolation(
					WorktimeViolation::TYPE_EDITED_ENDING,
					$this->getTimeHelper()->convertUtcTimestampToDaySeconds(
						$record['RECORDED_STOP_TIMESTAMP'],
						$record['STOP_OFFSET']
					),
					$record['ACTUAL_STOP_TIMESTAMP'] - $record['RECORDED_STOP_TIMESTAMP']
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
				$recordedStartSeconds,
				$recordedStartSeconds - $this->getShift()->getWorkTimeStart() - $maxOffsetStart
			);
		}

		return $violations;
	}

	private function buildExactStartViolations($recordedStartSeconds)
	{
		$violations = [];
		$violationsConfig = $this->getViolationRules();
		$maxExactStart = $violationsConfig['MAX_EXACT_START'];
		if (!ViolationRules::isViolationConfigured($maxExactStart))
		{
			return [];
		}

		if ($recordedStartSeconds > $maxExactStart)
		{
			$violations[] = $this->createViolation(
				WorktimeViolation::TYPE_LATE_START,
				$recordedStartSeconds,
				$recordedStartSeconds - $maxExactStart
			);
		}

		return $violations;
	}

	private function buildRelativeStartViolations($recordedStartSeconds)
	{
		$violations = [];
		$violationsConfig = $this->getViolationRules();
		$startFrom = $violationsConfig['RELATIVE_START_FROM'];
		if (ViolationRules::isViolationConfigured($startFrom))
		{
			if ($recordedStartSeconds < $startFrom)
			{
				$violations[] = $this->createViolation(
					WorktimeViolation::TYPE_EARLY_START,
					$recordedStartSeconds,
					$recordedStartSeconds - $startFrom
				);
			}
		}

		$startTo = $violationsConfig['RELATIVE_START_TO'];
		if (ViolationRules::isViolationConfigured($startTo))
		{
			if ($recordedStartSeconds > $startTo)
			{
				$violations[] = $this->createViolation(
					WorktimeViolation::TYPE_LATE_START,
					$recordedStartSeconds,
					$recordedStartSeconds - $startTo
				);
			}
		}
		return $violations;
	}

	private function buildOffsetStopViolations($recordedStopSeconds)
	{
		$minOffsetEnd = $this->getViolationRules()['MIN_OFFSET_END'];
		if (!$this->getShift() || !ViolationRules::isViolationConfigured($minOffsetEnd))
		{
			return [];
		}

		$violations = [];

		if ($recordedStopSeconds < $this->getShift()->getWorkTimeEnd() - $minOffsetEnd)
		{
			$violations[] = $this->createViolation(
				WorktimeViolation::TYPE_EARLY_ENDING,
				$recordedStopSeconds,
				$recordedStopSeconds - $this->getShift()->getWorkTimeEnd() + $minOffsetEnd
			);
		}

		return $violations;
	}

	private function buildExactStopViolations($recordedStopSeconds)
	{
		$violations = [];
		$violationsConfig = $this->getViolationRules();
		$minExactEnd = $violationsConfig['MIN_EXACT_END'];
		if (!ViolationRules::isViolationConfigured($minExactEnd))
		{
			return [];
		}

		if ($recordedStopSeconds < $minExactEnd)
		{
			$violations[] = $this->createViolation(
				WorktimeViolation::TYPE_EARLY_ENDING,
				$recordedStopSeconds,
				$recordedStopSeconds - $minExactEnd
			);
		}

		return $violations;
	}

	private function buildRelativeStopViolations($recordedStopSeconds)
	{
		$violations = [];
		$violationsConfig = $this->getViolationRules();
		$stopFrom = $violationsConfig['RELATIVE_END_FROM'];
		if (ViolationRules::isViolationConfigured($stopFrom))
		{
			if ($recordedStopSeconds < $stopFrom)
			{
				$violations[] = $this->createViolation(
					WorktimeViolation::TYPE_EARLY_ENDING,
					$recordedStopSeconds,
					$recordedStopSeconds - $stopFrom
				);
			}
		}

		$stopTo = $violationsConfig['RELATIVE_END_TO'];
		if (ViolationRules::isViolationConfigured($stopTo))
		{
			if ($recordedStopSeconds > $stopTo)
			{
				$violations[] = $this->createViolation(
					WorktimeViolation::TYPE_LATE_ENDING,
					$recordedStopSeconds,
					$recordedStopSeconds - $stopTo
				);
			}
		}
		return $violations;
	}

	protected function findActiveUsers(Schedule $schedule)
	{
		return $this->scheduleRepository->findActiveUsers($schedule);
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
				$this->getCurrentUserId(),
				$userIds
			);
	}

	protected function findRecordsForPeriod(\DateTime $fromDateTime, \DateTime $toDateTime, Schedule $schedule, $userIds)
	{
		return $this->worktimeRepository
			->findRecordsForPeriod($fromDateTime, $toDateTime, $schedule, $userIds);
	}
}