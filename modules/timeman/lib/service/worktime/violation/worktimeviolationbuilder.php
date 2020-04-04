<?php
namespace Bitrix\Timeman\Service\Worktime\Violation;

use Bitrix\Main\Error;
use Bitrix\Main\Type\DateTime;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Schedule\Calendar\Calendar;
use Bitrix\Timeman\Model\Schedule\Shift\Shift;
use Bitrix\Timeman\Repository\AbsenceRepository;
use Bitrix\Timeman\Repository\Schedule\CalendarRepository;
use Bitrix\Timeman\Repository\Schedule\ScheduleRepository;
use Bitrix\Timeman\Repository\Schedule\ShiftPlanRepository;
use Bitrix\Timeman\Repository\Schedule\ShiftRepository;
use Bitrix\Timeman\Repository\Worktime\WorktimeRepository;

class WorktimeViolationBuilder
{
	/** @var WorktimeViolationManager */
	protected $manager;
	private $calendars;
	/** @var WorktimeViolationParams */
	private $violationParams;

	protected $shiftRepository;
	protected $shiftPlanRepository;
	protected $worktimeRepository;
	protected $calendarRepository;
	protected $scheduleRepository;
	protected $absenceRepository;
	private $absenceData;

	public function __construct(
		WorktimeViolationParams $params,
		ShiftRepository $shiftRepository,
		ShiftPlanRepository $shiftPlanRepository,
		WorktimeRepository $worktimeRepository,
		CalendarRepository $calendarRepository,
		ScheduleRepository $scheduleRepository,
		AbsenceRepository $absenceRepository)
	{
		$this->violationParams = $params;
		$this->shiftRepository = $shiftRepository;
		$this->shiftPlanRepository = $shiftPlanRepository;
		$this->worktimeRepository = $worktimeRepository;
		$this->calendarRepository = $calendarRepository;
		$this->scheduleRepository = $scheduleRepository;
		$this->absenceRepository = $absenceRepository;
	}

	public function buildViolations($types = [])
	{
		$record = $this->getRecord();
		if (!$record || !$this->getSchedule())
		{
			return [];
		}
		$violations = [];
		if (!$this->skipViolationsCheck())
		{
			$violations = array_merge($violations, $this->buildStartViolations());
			if ($this->issetProperty($record['RECORDED_STOP_TIMESTAMP']))
			{
				$violations = array_merge($violations, $this->buildDurationViolations());
				$violations = array_merge($violations, $this->buildEndViolations());
			}
		}

		/** @var WorktimeViolation[] $violations */
		$violations = array_filter(array_merge(
			$violations,
			$this->buildEditViolations()
		));

		if (!empty($types))
		{
			foreach ($violations as $index => $violation)
			{
				if (!in_array($violation->type, $types, true))
				{
					unset($violations[$index]);
				}
			}
		}
		return array_filter($violations);
	}

	protected function buildDurationViolations()
	{
		return [];
	}

	protected function buildEditViolations()
	{
		return [];
	}

	protected function buildStartViolations()
	{
		return [];
	}

	protected function buildEndViolations()
	{
		return [];
	}

	public function buildEditedWorktimeWarnings($checkAllowedDelta)
	{
		return [];
	}

	/**
	 * @param $shift
	 * @param $userId
	 * @param $dateFormatted
	 * @return WorktimeViolationResult
	 */
	public function buildMissedShiftViolation($shift, $userId, $dateFormatted)
	{
		return (new WorktimeViolationResult())->addError(new Error('', WorktimeViolationResult::ERROR_CODE_NOT_IMPLEMENTED_YET));
	}

	/**
	 * @param WorktimeViolationParams $schedule
	 * @param \DateTime $fromDateTime
	 * @param \DateTime $toDateTime
	 * @return WorktimeViolationResult
	 */
	public function buildPeriodTimeLackViolation($params, $fromDateTime, $toDateTime)
	{
		return (new WorktimeViolationResult())->addError(new Error('', WorktimeViolationResult::ERROR_CODE_NOT_IMPLEMENTED_YET));
	}

	public function setWorktimeViolationParams(WorktimeViolationParams $params)
	{
		$this->violationParams = $params;
	}

	/**
	 * @param $type
	 * @param $recordedSeconds
	 * @param $violatedSeconds
	 * @param null $violationRank
	 * @return WorktimeViolation|mixed
	 */
	protected function createViolation($type, $recordedSeconds = null, $violatedSeconds = null, $userId = null)
	{
		$violation = new WorktimeViolation();
		$violation->violationRules = $this->getViolationRules();
		$violation->userId = $userId;
		if (!$violation->userId && $this->getRecord())
		{
			$violation->userId = $this->getRecord()['USER_ID'];
		}
		$violation->type = $type;
		$violation->recordedSeconds = $recordedSeconds;
		$violation->violatedSeconds = $violatedSeconds;
		if ($this->getCreateViolationCallback())
		{
			return call_user_func($this->getCreateViolationCallback(), $violation);
		}
		return $violation;
	}

	protected function getRecordStartDateTime()
	{
		return $this->getTimeHelper()
			->createDateTimeFromFormat('U', $this->getRecord()['RECORDED_START_TIMESTAMP'], $this->getRecord()['START_OFFSET']);
	}

	protected function isDateTimeHoliday(\DateTime $dateTime)
	{
		$calendar = $this->getCalendar($this->getSchedule()['CALENDAR_ID'], $dateTime->format('Y'));
		if ($calendar && !empty($calendar->obtainFinalExclusions()[$dateTime->format('Y')]))
		{
			return isset($calendar->obtainFinalExclusions()[$dateTime->format('Y')][$dateTime->format('n')][$dateTime->format('j')]);
		}
		return false;
	}

	private function isRecordStartedOnHoliday()
	{
		$startDateTime = $this->getRecordStartDateTime();
		return $this->isDateTimeHoliday($startDateTime);
	}

	/**
	 * @param $calendarId
	 * @param $year
	 * @return Calendar
	 */
	protected function getCalendar($calendarId, $year)
	{
		$key = $calendarId . $year;
		if ($this->calendars[$key] === null)
		{
			$this->calendars[$key] = $this->calendarRepository
				->findByIdWithParentCalendarExclusions($calendarId, $year);
		}
		return $this->calendars[$key];
	}

	protected function issetProperty($value)
	{
		if ($value === 0 || $value === '0' || is_null($value))
		{
			return false;
		}
		return true;
	}

	protected function getSchedule()
	{
		return $this->violationParams->getSchedule();
	}

	protected function getViolationRules()
	{
		return $this->violationParams->getViolationRules();
	}

	protected function getShift()
	{
		return $this->violationParams->getShift();
	}

	protected function getCurrentUserId()
	{
		return $this->violationParams->getCurrentUserId();
	}

	protected function getRecord()
	{
		return $this->violationParams->getRecord();
	}

	/**
	 * @return TimeHelper
	 */
	protected function getTimeHelper()
	{
		return TimeHelper::getInstance();
	}

	private function getAbsenceData()
	{
		if ($this->absenceData === null)
		{
			return $this->violationParams->getAbsenceData();
		}
		return $this->absenceData;
	}

	protected function setAbsenceData($data)
	{
		$this->absenceData = $data;
	}

	/**
	 * @param $userId
	 * @param \DateTime|DateTime $dateTime
	 * @return array
	 */
	private function findAbsenceData($userId, $dateTime)
	{
		$shiftDuration = $this->getShift() ? Shift::getShiftDuration($this->getShift()) : 0;

		return $this->absenceRepository
			->findAbsences(
				convertTimeStamp($dateTime->getTimestamp(), 'FULL'),
				convertTimeStamp($dateTime->getTimestamp() + $shiftDuration, 'FULL'),
				$this->getCurrentUserId(),
				$userId
			);
	}

	/**
	 * @param $userId
	 * @param \DateTime|DateTime $recordDateTime
	 * @return bool
	 */
	protected function isUserWasAbsent($userId, $recordDateTime)
	{
		if ($this->getAbsenceData() === null)
		{
			$absenceData = $this->findAbsenceData($userId, $recordDateTime);
			$data = [];
			$data[$userId] = $absenceData;
			$this->setAbsenceData($data);
		}
		if (empty($this->getAbsenceData()[$userId]))
		{
			return false;
		}
		foreach ($this->getAbsenceData()[$userId] as $absenceFields)
		{
			/** @var \DateTime $dateFrom */
			$dateFrom = $absenceFields['tm_absStartDateTime'];
			/** @var \DateTime $dateTo */
			$dateTo = $absenceFields['tm_absEndDateTime'];
			if (!($dateFrom && $dateTo))
			{
				return false;
			}
			if ($dateFrom->format('d.m.Y') === $recordDateTime->format('d.m.Y')
				||
				$dateTo->format('d.m.Y') === $recordDateTime->format('d.m.Y')
			)
			{
				return true;
			}

			if ($recordDateTime->getTimestamp() >= $dateFrom->getTimestamp()
				&& $recordDateTime->getTimestamp() <= $dateTo->getTimestamp())
			{
				return true;
			}
		}
		return false;
	}

	protected function skipViolationsCheck()
	{
		$userWasAbsent = $this->isUserWasAbsent($this->getRecord()['USER_ID'], $this->getRecordStartDateTime());

		return $this->isRecordStartedOnHoliday()
			   || $userWasAbsent;
	}

	protected function getShiftPlans()
	{
		return $this->violationParams->getShiftPlans();
	}

	private function getCreateViolationCallback()
	{
		return $this->violationParams->getCreateViolationCallback();
	}
}