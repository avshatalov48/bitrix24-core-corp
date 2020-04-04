<?php
namespace Bitrix\Timeman\Model\Schedule;

use Bitrix\Timeman\Form\Schedule\ScheduleForm;
use Bitrix\Timeman\Form\Schedule\ViolationForm;
use Bitrix\Timeman\Form\Schedule\WorktimeRestrictionsForm;
use Bitrix\Timeman\Helper\ConfigurationHelper;
use Bitrix\Timeman\Helper\EntityCodesHelper;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Schedule\Assignment\Department\EO_ScheduleDepartment_Collection;
use Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartment;
use Bitrix\Timeman\Model\Schedule\Assignment\User\EO_ScheduleUser_Collection;
use Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUser;
use Bitrix\Timeman\Model\Schedule\Shift\Shift;
use Bitrix\Timeman\Model\Schedule\Violation\ViolationRules;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord;

class Schedule extends EO_Schedule
{
	const FIXED_MAX_START_OFFSET = 6 * 3600;

	private $activeUsers = [];
	private $excludedUsers = [];
	private $usersCount = 0;

	public static function create(ScheduleForm $scheduleForm, $calendarId)
	{
		$schedule = new static();
		$schedule->setName($scheduleForm->name);
		$schedule->setScheduleType($scheduleForm->type);
		$schedule->setReportPeriod($scheduleForm->reportPeriod);
		$schedule->setReportPeriodOptions([
			ScheduleTable::REPORT_PERIOD_OPTIONS_START_WEEK_DAY => ScheduleTable::REPORT_PERIOD_OPTIONS_START_WEEK_DAY_MONDAY,
		]);
		if ($scheduleForm->reportPeriodStartWeekDay !== null)
		{
			$schedule->setReportPeriodOptions([
				ScheduleTable::REPORT_PERIOD_OPTIONS_START_WEEK_DAY => $scheduleForm->reportPeriodStartWeekDay,
			]);
		}
		$schedule->setControlledActions($scheduleForm->controlledActions);
		$schedule->setCalendarId($calendarId);
		$schedule->defineAllowedDevices($scheduleForm->allowedDevices);
		$schedule->setIsForAllUsers((bool)$scheduleForm->isForAllUsers);
		$schedule->setFlexibleScheduleSettings();
		$schedule->defineWorktimeRestrictions($scheduleForm->restrictionsForm);

		return $schedule;
	}

	public function isControlledActionsStartOnly()
	{
		return $this->getControlledActions() === ScheduleTable::CONTROLLED_ACTION_START;
	}

	/**
	 * @param ScheduleForm $scheduleForm
	 * @param ViolationForm $violationForm
	 */
	public function edit(ScheduleForm $scheduleForm)
	{
		$this->setName($scheduleForm->name);
		$this->setScheduleType($scheduleForm->type);
		$this->setReportPeriod($scheduleForm->reportPeriod);
		if ($scheduleForm->reportPeriodStartWeekDay !== null)
		{
			$this->setReportPeriodOptions([
				ScheduleTable::REPORT_PERIOD_OPTIONS_START_WEEK_DAY => $scheduleForm->reportPeriodStartWeekDay,
			]);
		}
		$this->setControlledActions($scheduleForm->controlledActions);
		$this->setIsForAllUsers((bool)$scheduleForm->isForAllUsers);
		$this->defineAllowedDevices($scheduleForm->allowedDevices);
		$this->setFlexibleScheduleSettings();
		$this->defineWorktimeRestrictions($scheduleForm->restrictionsForm);
	}

	public static function isDeviceAllowed($device, $schedule)
	{
		if (!$schedule)
		{
			return true;
		}
		$allowedDevices = $schedule['ALLOWED_DEVICES'];
		if (!$device)
		{
			return true;
		}
		if (empty($allowedDevices))
		{
			return false;
		}
		if (!in_array($device, array_keys($allowedDevices), true))
		{
			return false;
		}
		return $allowedDevices[$device] === true;
	}

	public static function getFixedScheduleTypeName()
	{
		return ScheduleTable::SCHEDULE_TYPE_FIXED;
	}

	public static function getFlextimeScheduleTypeName()
	{
		return ScheduleTable::SCHEDULE_TYPE_FLEXTIME;
	}

	public static function getShiftedScheduleTypeName()
	{
		return ScheduleTable::SCHEDULE_TYPE_SHIFT;
	}

	public function getReportPeriodStartWeekDay()
	{
		if ($this->getReportPeriodOptions() === null)
		{
			return null;
		}
		if (array_key_exists(ScheduleTable::REPORT_PERIOD_OPTIONS_START_WEEK_DAY, $this->getReportPeriodOptions()))
		{
			return $this->getReportPeriodOptions()[ScheduleTable::REPORT_PERIOD_OPTIONS_START_WEEK_DAY];
		}
		return null;
	}

	public static function isScheduleTypeShifted($type)
	{
		return $type === static::getShiftedScheduleTypeName();
	}

	public static function isScheduleTypeFlextime($type)
	{
		return $type === static::getFlextimeScheduleTypeName();
	}

	public static function isScheduleTypeFixed($type)
	{
		return $type === static::getFixedScheduleTypeName();
	}

	public static function isControlledActionsConfigured($schedule)
	{
		return static::isValueConfigured($schedule['CONTROLLED_ACTIONS']);
	}

	private static function isValueConfigured($value)
	{
		return $value !== null && $value !== -1;
	}

	public function defineUsersCount($usersCount)
	{
		$this->usersCount = $usersCount;
		return $this;
	}

	public function obtainUsersCount()
	{
		return $this->usersCount;
	}

	public function defineAllowedDevices($allowedDevices)
	{
		if (!(isset($allowedDevices[ScheduleTable::ALLOWED_DEVICES_MOBILE])
			  && $allowedDevices[ScheduleTable::ALLOWED_DEVICES_MOBILE] === true))
		{
			unset($allowedDevices['mobileRecordLocation']);
		}
		$this->setAllowedDevices($allowedDevices);
	}


	public function isShifted()
	{
		return static::isScheduleTypeShifted($this->getScheduleType());
	}

	public static function isScheduleShifted($schedule)
	{
		return $schedule && static::isScheduleTypeShifted($schedule['SCHEDULE_TYPE']);
	}

	public function isFlexible()
	{
		return static::isScheduleFlexible($this);
	}

	public function isFixed()
	{
		return static::isScheduleFixed($this);
	}

	public static function isScheduleFlexible($schedule)
	{
		return $schedule && $schedule['SCHEDULE_TYPE'] === ScheduleTable::SCHEDULE_TYPE_FLEXTIME;
	}

	public static function isScheduleFixed($schedule)
	{
		return $schedule && static::isScheduleTypeFixed($schedule['SCHEDULE_TYPE']);
	}

	public function isAutoStarting()
	{
		return static::isAutoStartingEnabledForSchedule($this);
	}

	public static function isAutoStartingEnabledForSchedule($schedule)
	{
		return static::isControlledActionsConfigured($schedule)
			   &&
			   in_array(
				   (int)$schedule['CONTROLLED_ACTIONS'],
				   [ScheduleTable::CONTROLLED_ACTION_END],
				   true
			   );
	}

	public static function isAutoClosingEnabledForSchedule($schedule)
	{
		return static::isControlledActionsConfigured($schedule)
			   && (int)$schedule['CONTROLLED_ACTIONS'] === ScheduleTable::CONTROLLED_ACTION_START;
	}

	public function isAutoClosing()
	{
		return static::isAutoClosingEnabledForSchedule($this);
	}

	public function markDeleted()
	{
		$this->setDeleted(ScheduleTable::DELETED_YES);
	}

	public function isAllowedToReopenRecord()
	{
		return static::getScheduleRestriction($this, ScheduleTable::WORKTIME_RESTRICTION_ALLOWED_TO_REOPEN_RECORD);
	}

	public function isAllowedToEditRecord()
	{
		return static::getScheduleRestriction($this, ScheduleTable::WORKTIME_RESTRICTION_ALLOWED_TO_EDIT_RECORD);
	}

	public static function getScheduleRestriction($schedule, $configName)
	{
		$value = isset($schedule['WORKTIME_RESTRICTIONS'][$configName]) ? $schedule['WORKTIME_RESTRICTIONS'][$configName] : null;
		if ($value === null)
		{
			switch ($configName)
			{
				case ScheduleTable::WORKTIME_RESTRICTION_ALLOWED_TO_EDIT_RECORD:
					$value = ConfigurationHelper::getInstance()->getIsAllowedToEditDay();
					break;
				case ScheduleTable::WORKTIME_RESTRICTION_ALLOWED_TO_REOPEN_RECORD:
					$value = ConfigurationHelper::getInstance()->getIsAllowedToReopenDay();
					break;
				default:
					break;
			}
		}
		return $value;
	}

	public function defineActiveUsers($users)
	{
		$this->activeUsers = [];
		foreach ($users as $user)
		{
			$this->activeUsers[$user['ID']] = $user;
		}
	}

	public function obtainActiveUsers()
	{
		return $this->activeUsers;
	}

	public function obtainActiveUserIds()
	{
		return array_map(function ($user) {
			return $user['ID'];
		}, $this->activeUsers);
	}

	public function containsActiveUser($userId)
	{
		return isset($this->activeUsers[$userId]);
	}

	public function defineExcludedUsers($users)
	{
		$this->excludedUsers = [];
		foreach ($users as $user)
		{
			$this->excludedUsers[$user['ID']] = $user;
		}
	}

	public function obtainFromAssignments($userId)
	{
		if (empty($this->obtainUserAssignments()))
		{
			return null;
		}

		return $this->obtainUserAssignments()->getByPrimary([
			'SCHEDULE_ID' => $this->getId(),
			'USER_ID' => $userId,
		]);
	}

	/**
	 * @return EO_ScheduleUser_Collection
	 */
	public function obtainUserAssignments()
	{
		try
		{
			$assignments = $this->get('USER_ASSIGNMENTS');
			return $assignments === null ? new EO_ScheduleUser_Collection() : $assignments;
		}
		catch (\Exception $exc)
		{
			return new EO_ScheduleUser_Collection();
		}
	}

	/**
	 * @return EO_ScheduleDepartment_Collection
	 */
	public function obtainDepartmentAssignments()
	{
		try
		{
			$items = $this->get('DEPARTMENT_ASSIGNMENTS');
			return !($items instanceof EO_ScheduleDepartment_Collection) ? new EO_ScheduleDepartment_Collection() : $items;
		}
		catch (\Exception $exc)
		{
			return new EO_ScheduleDepartment_Collection();
		}
	}

	public function obtainShiftByPrimary($shiftId)
	{
		if (!$this->obtainShifts() || !$shiftId)
		{
			return null;
		}
		foreach ($this->obtainShifts() as $shift)
		{
			if ($shift->getId() === (int)$shiftId)
			{
				return $shift;
			}
		}
		return null;
	}

	/**
	 * @return Shift[]
	 */
	public function obtainShifts()
	{
		foreach (['SHIFTS', 'ALL_SHIFTS'] as $key)
		{
			try
			{
				$shifts = $this->get($key);
				if ($shifts === null)
				{
					continue;
				}
				$result = [];
				foreach ($shifts as $shift)
				{
					$result[] = $shift;
				}
				return $result;
			}
			catch (\Exception $exc)
			{
			}
		}
		return [];
	}

	/**
	 * @return ViolationRules|null
	 */
	public function obtainScheduleViolationRules()
	{
		$nullViolationRulesObject = ViolationRules::create($this->getId());
		try
		{
			$violationRules = $this->get('SCHEDULE_VIOLATION_RULES');
			if ($violationRules)
			{
				return $violationRules;
			}
			return $nullViolationRulesObject;
		}
		catch (\Exception $exc)
		{
			return $nullViolationRulesObject;
		}
	}

	private function setFlexibleScheduleSettings()
	{
		if (!$this->isFlexible())
		{
			return;
		}
		$this->setControlledActions(ScheduleTable::CONTROLLED_ACTION_START_AND_END);
	}

	/**
	 * @return Shift[]
	 */
	public function obtainActiveShifts()
	{
		$result = [];
		$shifts = $this->obtainShifts();
		foreach ($shifts as $shift)
		{
			if ($shift->isActive())
			{
				$result[] = $shift;
			}
		}
		return $result;
	}

	/**
	 * @param \DateTime $userDateTime
	 * @return Shift[]
	 */
	public function getAllShiftsByTime($userDateTime)
	{
		$userSeconds = TimeHelper::getInstance()->getSecondsFromDateTime($userDateTime);
		$matchedShifts = [];
		foreach ($this->obtainActiveShifts() as $shift)
		{
			if ($shift->isForTime($userSeconds, $this->getAllowedMaxShiftStartOffset()))
			{
				$matchedShifts[$shift->getId()] = $shift;
			}
		}
		return $matchedShifts;
	}

	/**
	 * @param \DateTime $userDateTime
	 * @param WorktimeRecord $record
	 * @param null $recordSchedule
	 * @param null $recordShift
	 * @return Shift|null
	 */
	public function getShiftByTime($userDateTime)
	{
		$userSeconds = TimeHelper::getInstance()->getSecondsFromDateTime($userDateTime);
		$matchedShifts = $this->getAllShiftsByTime($userDateTime);

		if (count($matchedShifts) <= 1)
		{
			return empty($matchedShifts) ? null : reset($matchedShifts);
		}
		else
		{
			foreach ($matchedShifts as $shift)
			{
				$key = abs(TimeHelper::getInstance()->normalizeSeconds($shift->getWorkTimeEnd()) - $userSeconds);
				$sorted[$key] = $shift->getId();
			}
			ksort($sorted);

			return $matchedShifts[reset($sorted)];
		}
	}

	/**
	 * @param Shift $prevShift
	 * @param \DateTime $userNowDateTime
	 * @return Shift|mixed|null
	 */
	public function getNextShift($prevShift, $userNowDateTime = null)
	{
		$shifts = [];
		foreach ($this->obtainActiveShifts() as $shift)
		{
			$shifts[$shift->getId()] = $shift;
		}
		if (count($shifts) <= 1)
		{
			return empty($shifts) ? null : reset($shifts);
		}
		$shiftsByStart = [];
		foreach ($shifts as $shift)
		{
			$key = $shift->getWorkTimeStart();
			while (!empty($shiftsByStart[$key]))
			{
				$key = $key + 1;
			}
			$shiftsByStart[$key] = $shift;
		}
		ksort($shiftsByStart);
		if (!$prevShift)
		{
			$nowSeconds = TimeHelper::getInstance()->getSecondsFromDateTime($userNowDateTime);
			foreach ($shiftsByStart as $shift)
			{
				if ($shift->getWorkTimeStart() > $nowSeconds)
				{
					return $shift;
				}
			}
			return reset($shiftsByStart);
		}
		$shiftCount = count($shiftsByStart);
		for ($i = 0; $i < $shiftCount - 1; $i++)
		{
			$key = array_keys($shiftsByStart)[$i];
			if ($prevShift->getId() === $shiftsByStart[$key]->getId())
			{
				$key = array_keys($shiftsByStart)[$i + 1];
				return $shiftsByStart[$key];
			}
		}
		return reset($shifts);
	}

	public function collectRawValues()
	{
		return $this->collectValues(\Bitrix\Main\ORM\Objectify\Values::ALL, \Bitrix\Main\ORM\Fields\FieldTypeMask::FLAT);
	}

	public function getShiftByWeekDay($weekDay)
	{
		$shifts = $this->obtainActiveShifts();

		foreach ($shifts as $shift)
		{
			if ($shift->isForWeekDay($weekDay))
			{
				return $shift;
			}
		}
		return null;
	}

	/**
	 * @param WorktimeRestrictionsForm $restrictionsForm
	 */
	private function defineWorktimeRestrictions($restrictionsForm)
	{
		$restrictions = (array)$this->getWorktimeRestrictions();
		if ($restrictionsForm->maxShiftStartOffset >= 0 && $this->isShifted())
		{
			$restrictions[ScheduleTable::WORKTIME_RESTRICTION_MAX_SHIFT_START_OFFSET] = $restrictionsForm->maxShiftStartOffset;
		}
		if ($restrictionsForm->allowedToEditRecord !== null)
		{
			$restrictions[ScheduleTable::WORKTIME_RESTRICTION_ALLOWED_TO_EDIT_RECORD] = (bool)$restrictionsForm->allowedToEditRecord;
		}
		if ($restrictionsForm->allowedToReopenRecord !== null)
		{
			$restrictions[ScheduleTable::WORKTIME_RESTRICTION_ALLOWED_TO_REOPEN_RECORD] = (bool)$restrictionsForm->allowedToReopenRecord;
		}
		$this->setWorktimeRestrictions($restrictions);
	}

	public function obtainUserAssignmentsById($userId)
	{
		foreach ($this->obtainUserAssignments() as $userAssignment)
		{
			if ((int)$userAssignment['USER_ID'] === (int)$userId)
			{
				return $userAssignment;
			}
		}
		return null;
	}

	public function obtainAssignmentByCode($entityCode)
	{
		if (EntityCodesHelper::isDepartment($entityCode))
		{
			return $this->obtainDepartmentAssignmentById(EntityCodesHelper::getDepartmentId($entityCode));
		}
		if (EntityCodesHelper::isUser($entityCode))
		{
			return $this->obtainDepartmentAssignmentById(EntityCodesHelper::getUserId($entityCode));
		}
		return null;
	}

	public function obtainDepartmentAssignmentById($departmentId)
	{
		foreach ($this->obtainDepartmentAssignments() as $assignment)
		{
			if ((int)$assignment['DEPARTMENT_ID'] === (int)$departmentId)
			{
				return $assignment;
			}
		}
		return null;
	}

	public function obtainWorktimeRestrictions($name)
	{
		if (!empty($this->getWorktimeRestrictions()) && array_key_exists($name, $this->getWorktimeRestrictions()))
		{
			return $this->getWorktimeRestrictions()[$name];
		}
		return null;
	}

	public function getAllowedMaxShiftStartOffset()
	{
		return (int)$this->obtainWorktimeRestrictions(ScheduleTable::WORKTIME_RESTRICTION_MAX_SHIFT_START_OFFSET);
	}

	public function assignEntity($code, $excluded = false)
	{
		if (EntityCodesHelper::isUser($code))
		{
			$user = ScheduleUser::create($this->getId(), EntityCodesHelper::getUserId($code), $excluded);
			$this->addToUserAssignments($user);
		}
		elseif (EntityCodesHelper::isDepartment($code))
		{
			$depart = ScheduleDepartment::create($this->getId(), EntityCodesHelper::getDepartmentId($code), $excluded);
			$this->addToDepartmentAssignments($depart);
		}
	}

	/**
	 * @param \DateTime $userDateTime
	 * @return Shift|null
	 */
	public function getRelevantShiftByStart($userDateTime)
	{
		$previousWeekDay = TimeHelper::getInstance()->getPreviousDayOfWeek($userDateTime);
		$todayWeekDay = TimeHelper::getInstance()->getDayOfWeek($userDateTime);
		$nextWeekDay = TimeHelper::getInstance()->getNextDayOfWeek($userDateTime);

		$possibleStartsEnds = [];
		if ($todayShift = $this->getShiftByWeekDay($todayWeekDay))
		{
			$possibleStartsEnds = $todayShift->buildStartsEndsAroundDate($userDateTime);
		}
		if ($prevShift = $this->getShiftByWeekDay($previousWeekDay))
		{
			$possibleStartsEnds[$previousWeekDay] = $prevShift->buildStartsEndsAroundDate($userDateTime)[$previousWeekDay];
		}
		if ($nextShift = $this->getShiftByWeekDay($nextWeekDay))
		{
			$possibleStartsEnds[$nextWeekDay] = $nextShift->buildStartsEndsAroundDate($userDateTime)[$nextWeekDay];
		}

		$allPossibleDates = [];
		foreach ($possibleStartsEnds as $weekDay => $dates)
		{
			/** @var \DateTime $startDate */
			$startDate = $dates[0];
			/** @var \DateTime $endDate */
			$endDate = $dates[1];
			if ($userDateTime->getTimestamp() >= $startDate->getTimestamp()
				&& $userDateTime->getTimestamp() <= $endDate->getTimestamp())
			{
				// start between shift start and end
				return $this->getShiftByWeekDay($weekDay);
			}
			else
			{
				$allPossibleDates[abs($userDateTime->getTimestamp() - $startDate->getTimestamp())] = $this->getShiftByWeekDay($weekDay);
			}
		}

		ksort($allPossibleDates);
		if (reset(array_keys($allPossibleDates)) < static::FIXED_MAX_START_OFFSET)
		{
			return reset($allPossibleDates);
		}

		return null;
	}
}