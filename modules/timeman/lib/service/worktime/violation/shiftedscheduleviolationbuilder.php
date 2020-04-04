<?php
namespace Bitrix\Timeman\Service\Worktime\Violation;


use Bitrix\Main\Error;
use Bitrix\Timeman\Model\Schedule\Shift\Shift;
use Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlanTable;
use Bitrix\Timeman\Model\Schedule\Violation\ViolationRules;

class ShiftedScheduleViolationBuilder extends WorktimeViolationBuilder
{
	private $plans;

	/**
	 * @param Shift $shift
	 * @param $userId
	 * @param $dateFormatted
	 * @return WorktimeViolationBuilder|mixed
	 */
	public function buildMissedShiftViolation($shift, $userId, $dateFormatted)
	{
		$result = new WorktimeViolationResult();
		if (!$shift->obtainSchedule())
		{
			return $result;
		}
		if (!$shift->obtainSchedule()->obtainScheduleViolationRules()->isMissedShiftsControlEnabled())
		{
			return $result->addError(new Error('', WorktimeViolationResult::ERROR_CODE_VIOLATION_NOT_UNDER_CONTROL));
		}

		$shiftPlan = $this->shiftPlanRepository
			->findByComplexId(
				$shift->getId(),
				$userId,
				\Bitrix\Main\Type\Date::createFromPhp(\DateTime::createFromFormat(ShiftPlanTable::DATE_FORMAT, $dateFormatted))
			);
		if (!$shiftPlan)
		{
			return $result->addError(new Error('', WorktimeViolationResult::ERROR_CODE_SHIFT_PLAN_NOT_FOUND));
		}

		$userWasAbsent = $this->isUserWasAbsent($userId, \DateTime::createFromFormat(ShiftPlanTable::DATE_FORMAT, $dateFormatted));

		$workedRecord = $this->worktimeRepository
			->findRecordByUserShiftDate($userId, $shift->getId(), $dateFormatted);
		if ($workedRecord || $userWasAbsent)
		{
			return $result->addError(new Error('', WorktimeViolationResult::ERROR_CODE_NO_VIOLATION));
		}

		$violation = $this->createViolation(WorktimeViolation::TYPE_MISSED_SHIFT);
		return $result
			->setShift($shift)
			->setShiftPlan($shiftPlan)
			->setSchedule($shift->obtainSchedule())
			->addViolation($violation);
	}

	protected function buildStartViolations()
	{
		$shift = $this->getShift();
		if (!$shift)
		{
			return [];
		}
		$record = $this->getRecord();
		$recordedStartSeconds = $this->getTimeHelper()->convertUtcTimestampToDaySeconds(
			$record['RECORDED_START_TIMESTAMP'],
			$record['START_OFFSET']
		);
		$violations = [];
		if (ViolationRules::isViolationConfigured($this->getViolationRules()['MAX_SHIFT_START_DELAY']))
		{
			if (($recordedStartSeconds - $shift['WORK_TIME_START']) > $this->getViolationRules()['MAX_SHIFT_START_DELAY'])
			{
				$violations[] = $this->createViolation(
					WorktimeViolation::TYPE_SHIFT_LATE_START,
					$recordedStartSeconds,
					$recordedStartSeconds - $shift['WORK_TIME_START']
				);
			}
		}
		return $violations;
	}

	protected function getShiftPlans()
	{
		if (!$this->getShift())
		{
			return [];
		}
		if ($this->plans === null)
		{
			$this->plans = parent::getShiftPlans();
			if ($this->plans === null)
			{
				$this->plans = $this->shiftPlanRepository
					->findByScheduleShiftsUsersDates(
						$this->getSchedule()['ID'],
						[$this->getShift()['ID']],
						$this->getRecord()['USER_ID'],
						$this->getRecordStartDateTime()
					);
			}
		}

		return $this->plans;
	}

	protected function isWorkingByShiftPlan()
	{
		return isset($this->getShiftPlans()
			[$this->getRecordStartDateTime()->format('Y-m-d')]
			[$this->getRecord()['USER_ID']]
			[$this->getRecord()['SHIFT_ID']]
		);
	}

	protected function skipViolationsCheck()
	{
		return parent::skipViolationsCheck()
			   || !($this->isWorkingByShiftPlan());
	}
}