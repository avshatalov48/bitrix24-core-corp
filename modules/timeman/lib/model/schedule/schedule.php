<?php
namespace Bitrix\Timeman\Model\Schedule;

use Bitrix\Timeman\Form\Schedule\ScheduleForm;
use Bitrix\Timeman\Form\Schedule\ViolationForm;
use Bitrix\Timeman\Helper\Form\Schedule\ScheduleFormHelper;
use Bitrix\Timeman\Model\Schedule\Assignment\Department\EO_ScheduleDepartment_Collection;
use Bitrix\Timeman\Model\Schedule\Assignment\User\EO_ScheduleUser_Collection;
use Bitrix\Timeman\Model\Schedule\Shift\Shift;
use Bitrix\Timeman\Model\Schedule\Violation\ViolationRules;

class Schedule extends EO_Schedule
{
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

		return $schedule;
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

	public function getFormattedType()
	{
		return isset(ScheduleFormHelper::getScheduleTypes()[$this->getScheduleType()])
			? ScheduleFormHelper::getScheduleTypes()[$this->getScheduleType()]
			: '';
	}

	public function getFormattedPeriod()
	{
		return isset(ScheduleFormHelper::getReportPeriods()[$this->getReportPeriod()])
			? ScheduleFormHelper::getReportPeriods()[$this->getReportPeriod()]
			: '';
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
				case ScheduleTable::WORKTIME_RESTRICTION_ALLOWED_TO_REOPEN_RECORD:
					$value = true;
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
	 * @return EO_ScheduleUser_Collection|array
	 */
	public function obtainUserAssignments()
	{
		try
		{
			$assignments = $this->get('USER_ASSIGNMENTS');
			return $assignments === null ? [] : $assignments;
		}
		catch (\Exception $exc)
		{
			return [];
		}
	}

	/**
	 * @return EO_ScheduleDepartment_Collection|array
	 */
	public function obtainDepartmentAssignments()
	{
		try
		{
			$items = $this->get('DEPARTMENT_ASSIGNMENTS');
			return $items === null ? [] : $items;
		}
		catch (\Exception $exc)
		{
			return [];
		}
	}

	public function obtainShiftByPrimary($shiftId)
	{
		if (!$this->obtainShifts())
		{
			return null;
		}
		foreach ($this->obtainShifts() as $shift)
		{
			if ($shift->getId() == $shiftId)
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
		try
		{
			$shifts = $this->get('SHIFTS');
			if ($shifts === null)
			{
				return [];
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
			return [];
		}
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

	public function getShiftForWeekDay($weekDay, $shifts = [])
	{
		$shifts = $shifts ?: $this->obtainShifts();

		foreach ($shifts as $shift)
		{
			if ($shift->isForWeekDay($weekDay))
			{
				return $shift;
			}
		}
		return null;
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
}