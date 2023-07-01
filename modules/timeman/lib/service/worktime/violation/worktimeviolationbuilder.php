<?php
namespace Bitrix\Timeman\Service\Worktime\Violation;

use Bitrix\Main\Error;
use Bitrix\Main\Type\DateTime;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Schedule\Calendar\Calendar;
use Bitrix\Timeman\Model\Schedule\Violation\ViolationRules;
use Bitrix\Timeman\Provider\Schedule\ScheduleProvider;
use Bitrix\Timeman\Repository\AbsenceRepository;
use Bitrix\Timeman\Repository\Schedule\CalendarRepository;

class WorktimeViolationBuilder
{
	/** @var WorktimeViolationManager */
	protected $manager;
	private $calendars;
	/** @var WorktimeViolationParams */
	private $violationParams;

	protected $calendarRepository;
	protected $scheduleProvider;
	protected $absenceRepository;
	private $absenceData;

	public function __construct(
		WorktimeViolationParams $params,
		CalendarRepository $calendarRepository,
		ScheduleProvider $scheduleProvider,
		AbsenceRepository $absenceRepository)
	{
		$this->violationParams = $params;
		$this->calendarRepository = $calendarRepository;
		$this->scheduleProvider = $scheduleProvider;
		$this->absenceRepository = $absenceRepository;
	}

	public function buildViolations($types = [])
	{
		$record = $this->getRecord();
		if (!$record || !$this->getSchedule() || !$this->getViolationRules())
		{
			return [];
		}
		$violations = [];
		if (!$this->skipViolationsCheck())
		{
			$violations = array_merge($violations, $this->buildStartViolations());
			if ($record->getRecordedStopTimestamp() > 0)
			{
				$violationsConfig = $this->getViolationRules();
				$minExactEnd = $violationsConfig->getMinExactEnd();
				$skipEndViolations = false;
				if (ViolationRules::isViolationConfigured($minExactEnd) && $this->getShift())
				{
					$userStartDate = TimeHelper::getInstance()->createDateTimeFromFormat('U', $record->getRecordedStartTimestamp(), $record->getStartOffset());
					$userEndDate = TimeHelper::getInstance()->createDateTimeFromFormat('U', $record->getRecordedStopTimestamp(), $record->getStopOffset());
					if ($this->getShift()->getWorkTimeEnd() > $this->getShift()->getWorkTimeStart() &&
						(int)$userEndDate->format('d') > (int)$userStartDate->format('d')
					)
					{
						$skipEndViolations = true;
					}
				}
				$violations = array_merge($violations, $this->buildDurationViolations());
				if (!$skipEndViolations)
				{
					$violations = array_merge($violations, $this->buildEndViolations());
				}
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

	protected function buildEditViolations($checkAllowedDelta = true)
	{
		if ($checkAllowedDelta && !ViolationRules::isViolationConfigured($this->getViolationRules()->getMaxAllowedToEditWorkTime()))
		{
			return [];
		}
		$violations = [];
		if (!$this->getSchedule()->isAutoStarting())
		{
			$violations = array_merge($violations, $this->buildEditStartViolations($checkAllowedDelta));
		}
		if (!$this->getSchedule()->isAutoClosing())
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

		if (!($this->issetProperty($record->getRecordedStartTimestamp()) &&
			  $this->issetProperty($record->getActualStartTimestamp()))
		)
		{
			return [];
		}
		$allowedDiff = 0;
		if ($checkAllowedDelta)
		{
			$allowedDiff = $this->getViolationRules()->getMaxAllowedToEditWorkTime();
		}
		if (abs($record->getActualStartTimestamp() - $record->getRecordedStartTimestamp()) > $allowedDiff)
		{
			return [
				$this->createViolation(
					WorktimeViolation::TYPE_EDITED_START,
					$record->getRecordedStartTimestamp(),
					$record->getRecordedStartTimestamp() - $record->getActualStartTimestamp()
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
			$allowedDiff = $this->getViolationRules()->getMaxAllowedToEditWorkTime();
		}
		if (abs($record->getActualBreakLength() - $record->getTimeLeaks()) > $allowedDiff)
		{
			return [
				$this->createViolation(
					WorktimeViolation::TYPE_EDITED_BREAK_LENGTH,
					$record->getTimeLeaks(),
					$record->getTimeLeaks() - $record->getActualBreakLength()
				),
			];
		}
		return [];
	}

	private function buildEditStopViolations($checkAllowedDelta = true)
	{
		$record = $this->getRecord();

		if (!($this->issetProperty($record->getRecordedStopTimestamp()) &&
			  $this->issetProperty($record->getActualStopTimestamp()))
		)
		{
			return [];
		}
		$allowedDiff = 0;
		if ($checkAllowedDelta)
		{
			$allowedDiff = $this->getViolationRules()->getMaxAllowedToEditWorkTime();
		}
		if (abs($record->getRecordedStopTimestamp() - $record->getActualStopTimestamp()) > $allowedDiff)
		{
			return [
				$this->createViolation(
					WorktimeViolation::TYPE_EDITED_ENDING,
					$record->getRecordedStopTimestamp(),
					$record->getActualStopTimestamp() - $record->getRecordedStopTimestamp()
				),
			];
		}
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

	/**
	 * @param $shift
	 * @param $userId
	 * @param $dateFormatted
	 * @return WorktimeViolationResult
	 */
	public function buildMissedShiftViolation()
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
	 * @param $recordedTimeValue
	 * @param $violatedSeconds
	 * @param null $violationRank
	 * @return WorktimeViolation|mixed
	 */
	protected function createViolation($type, $recordedTimeValue = null, $violatedSeconds = null, $userId = null)
	{
		$violation = new WorktimeViolation();
		$violation->violationRules = $this->getViolationRules();
		$violation->userId = $userId;
		if (!$violation->userId && $this->getRecord())
		{
			$violation->userId = $this->getRecord()->getUserId();
		}
		$violation->type = $type;
		$violation->recordedTimeValue = $recordedTimeValue;
		$violation->violatedSeconds = $violatedSeconds;
		if ($this->getCreateViolationCallback())
		{
			return call_user_func($this->getCreateViolationCallback(), $violation);
		}
		return $violation;
	}

	protected function buildRecordStartDateTime()
	{
		return $this->getRecord()->buildRecordedStartDateTime();
	}

	/**
	 * @param $calendarId
	 * @param $year
	 * @return Calendar
	 */
	protected function getCalendar($calendarId, $year)
	{
		$key = $calendarId . $year;
		if (($this->calendars[$key] ?? null) === null)
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
		$shiftDuration = $this->getShift() ? $this->getShift()->getDuration() : 0;

		return $this->absenceRepository
			->findAbsences(
				convertTimeStamp($dateTime->getTimestamp(), 'FULL'),
				convertTimeStamp($dateTime->getTimestamp() + $shiftDuration, 'FULL'),
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
		if ($this->getAbsenceData() === null || !array_key_exists($userId, $this->getAbsenceData()))
		{
			$absenceData = $this->findAbsenceData($userId, $recordDateTime);
			$data = $this->getAbsenceData();
			$data[$userId] = empty($absenceData) ? [] : (array)$absenceData[$userId];
			$this->setAbsenceData($data);
		}
		if (empty($this->getAbsenceData()[$userId]))
		{
			return false;
		}
		foreach ($this->getAbsenceData()[$userId] as $absenceFields)
		{
			if (empty($absenceFields['tm_absStartDateTime']) ||
				empty($absenceFields['tm_absEndDateTime']))
			{
				continue;
			}
			/** @var \DateTime $dateFrom */
			$dateFrom = clone $absenceFields['tm_absStartDateTime'];
			$dateFrom->setTimezone($recordDateTime->getTimezone());
			/** @var \DateTime $dateTo */
			$dateTo = clone $absenceFields['tm_absEndDateTime'];
			$dateTo->setTimezone($recordDateTime->getTimezone());
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
		return $this->isUserWasAbsent($this->getRecord()->getUserId(), $this->buildRecordStartDateTime());
	}

	protected function getShiftPlan()
	{
		return $this->violationParams->getShiftPlan();
	}

	private function getCreateViolationCallback()
	{
		return $this->violationParams->getCreateViolationCallback();
	}
}