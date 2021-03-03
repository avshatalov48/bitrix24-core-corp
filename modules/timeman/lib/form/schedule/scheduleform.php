<?php
namespace Bitrix\Timeman\Form\Schedule;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\RandomSequence;
use Bitrix\Timeman\Helper\EntityCodesHelper;
use Bitrix\Timeman\Model\Schedule\Schedule;

use Bitrix\Timeman\Helper\Form\Schedule\ScheduleFormHelper;
use Bitrix\Timeman\Model\Schedule\ScheduleTable;
use Bitrix\Timeman\Util\Form\CompositeForm;
use Bitrix\Timeman\Util\Form\Filter;

Loc::loadMessages(__FILE__);

/**
 * @property ShiftForm[] $shiftForms
 * @property CalendarForm calendarForm
 * @property ViolationForm violationForm
 * @property WorktimeRestrictionsForm restrictionsForm
 * @method getShiftForm
 */
class ScheduleForm extends CompositeForm
{
	public $id;

	public $name;
	public $type;
	public $reportPeriod;
	public $reportPeriodStartWeekDay;
	public $controlledActions;

	public $allowedDevices = [];

	public $assignments = [];
	public $assignmentsExcluded = [];

	public $isForAllUsers;
	public $userIds = [];
	public $userIdsExcluded = [];
	public $departmentIds = [];
	public $departmentIdsExcluded = [];

	/** @var Schedule */
	private $schedule;

	const ALL_USERS = 'UA';

	/**
	 * 'formInternalName' => class
	 * @return array
	 */
	protected function getInternalForms()
	{
		return [
			'shiftForms' => ShiftForm::class,
			'calendarForm' => CalendarForm::class,
			'violationForm' => ViolationForm::class,
			'restrictionsForm' => WorktimeRestrictionsForm::class,
		];
	}

	public function __construct($schedule = null)
	{
		if (!($schedule instanceof Schedule))
		{
			$this->shiftForms = [new ShiftForm()];
			$this->calendarForm = new CalendarForm();
			$this->restrictionsForm = new WorktimeRestrictionsForm();
			$this->violationForm = new ViolationForm();
			return;
		}
		$this->schedule = $schedule;
		# editing
		$this->name = $schedule->getName();
		$this->type = $schedule->getScheduleType();
		$this->reportPeriod = $schedule->getReportPeriod();
		$this->reportPeriodStartWeekDay = $schedule->getReportPeriodStartWeekDay();
		$this->allowedDevices = $schedule->getAllowedDevices();
		$this->controlledActions = $schedule->getControlledActions();
		$this->isForAllUsers = $schedule->getIsForAllUsers();
		if ($this->isForAllUsers)
		{
			$this->assignments[] = static::ALL_USERS;
		}
		foreach ($schedule->obtainUserAssignments() as $user)
		{
			$code = 'U' . $user->getUserId();
			if ($user->isExcluded())
			{
				$this->assignmentsExcluded[] = $code;
				$this->userIdsExcluded[] = $user->getUserId();
			}
			else
			{
				$this->assignments[] = $code;
				$this->userIds[] = $user->getUserId();
			}
		}

		foreach ($schedule->obtainDepartmentAssignments() as $item)
		{
			$code = 'DR' . $item->getDepartmentId();
			if ($item->isExcluded())
			{
				$this->departmentIdsExcluded[] = $item->getDepartmentId();
				$this->assignmentsExcluded[] = $code;
			}
			else
			{
				$this->departmentIds[] = $item->getDepartmentId();
				$this->assignments[] = $code;
			}
		}

		$this->shiftForms = $shiftForms = [];
		foreach ($schedule->obtainShifts() as $shift)
		{
			$shiftForms[] = new ShiftForm($shift);
		}
		if ($shiftForms)
		{
			$this->shiftForms = $shiftForms;
		}

		$this->restrictionsForm = new WorktimeRestrictionsForm($schedule);
		$this->calendarForm = new CalendarForm($schedule->getCalendar());
		$this->violationForm = new ViolationForm($schedule->obtainScheduleViolationRules());
	}

	public function deleteDuplicatedAssignments()
	{
		$this->userIds = array_unique($this->userIds);
		$this->userIdsExcluded = array_unique($this->userIdsExcluded);
		$this->departmentIds = array_unique($this->departmentIds);
		$this->departmentIdsExcluded = array_unique($this->departmentIdsExcluded);
		$this->deleteDuplicatesBetween('userIds', 'userIdsExcluded');
		$this->deleteDuplicatesBetween('departmentIds', 'departmentIdsExcluded');
	}

	private function deleteDuplicatesBetween($firstFieldName, $secondFieldName)
	{
		$bothAreNotEmptyArrays = !empty($this->{$firstFieldName}) && !empty($this->{$secondFieldName})
								 && is_array($this->{$firstFieldName}) && is_array($this->{$secondFieldName});
		if (!$bothAreNotEmptyArrays)
		{
			return;
		}

		$duplicates = array_intersect($this->{$firstFieldName}, $this->{$secondFieldName});
		if (empty($duplicates))
		{
			return;
		}
		$this->deleteDuplicatesFromArray($firstFieldName, $duplicates);
		$this->deleteDuplicatesFromArray($secondFieldName, $duplicates);
	}

	private function deleteDuplicatesFromArray($firstFieldName, $duplicates)
	{
		$this->{$firstFieldName} = array_values(
			array_filter($this->{$firstFieldName},
				function ($elem) use ($duplicates) {
					return !in_array($elem, $duplicates);
				}
			)
		);
	}

	public function getShiftForms()
	{
		return (array)$this->shiftForms;
	}

	public function getShiftIds()
	{
		if (empty($this->getShiftForms()))
		{
			return [];
		}
		$shiftIds = array_map(function ($shiftForm) {
			return $shiftForm->shiftId;
		}, $this->getShiftForms());
		return array_filter(array_unique($shiftIds));
	}

	public function isBrowserDeviceAllowed()
	{
		return isset($this->allowedDevices[ScheduleTable::ALLOWED_DEVICES_BROWSER]) ? (bool)$this->allowedDevices[ScheduleTable::ALLOWED_DEVICES_BROWSER] : false;
	}

	public function isB24TimeDeviceAllowed()
	{
		return isset($this->allowedDevices[ScheduleTable::ALLOWED_DEVICES_B24TIME]) ? (bool)$this->allowedDevices[ScheduleTable::ALLOWED_DEVICES_B24TIME] : false;
	}

	public function isMobileDeviceAllowed()
	{
		return isset($this->allowedDevices[ScheduleTable::ALLOWED_DEVICES_MOBILE]) ? (bool)$this->allowedDevices[ScheduleTable::ALLOWED_DEVICES_MOBILE] : false;
	}

	public function getMobileRecordLocation()
	{
		return isset($this->allowedDevices['mobileRecordLocation']) ? (bool)$this->allowedDevices['mobileRecordLocation'] : false;
	}

	public function isFixed()
	{
		return $this->type === null ? null : Schedule::isScheduleTypeFixed($this->type);
	}

	public function isShifted()
	{
		return $this->type === null ? null : Schedule::isScheduleTypeShifted($this->type);
	}

	protected function runAfterValidate()
	{
		parent::runAfterValidate();
		if (!$this->hasErrors('assignments'))
		{
			$this->fillAssignments('assignments', 'userIds', 'departmentIds', true);
		}
		if (!$this->hasErrors('assignmentsExcluded'))
		{
			$this->fillAssignments('assignmentsExcluded', 'userIdsExcluded', 'departmentIdsExcluded');
		}

		// remove ids if they are included and excluded at the same time
		foreach (array_intersect($this->userIds, $this->userIdsExcluded) as $id)
		{
			$this->userIds = $this->filterDuplicateId($this->userIds, $id);
			$this->userIdsExcluded = $this->filterDuplicateId($this->userIdsExcluded, $id);
		}
		foreach (array_intersect($this->departmentIds, $this->departmentIdsExcluded) as $id)
		{
			$this->departmentIds = $this->filterDuplicateId($this->departmentIds, $id);
			$this->departmentIdsExcluded = $this->filterDuplicateId($this->departmentIdsExcluded, $id);
		}

		if ($this->isFixed() && !empty($this->shiftForms))
		{
			$days = [];
			foreach ($this->shiftForms as $shiftForm)
			{
				if ($shiftForm->workDays)
				{
					$days = array_merge($days, str_split($shiftForm->workDays));
				}
			}
			if (count(array_unique($days)) !== count($days))
			{
				$this->addError('shiftForms.0.workDays', Loc::getMessage('TM_SCHEDULE_FORM_ERROR_DUPLICATE_WORK_DAYS'));
			}
		}
	}

	private function fillAssignments($assignmentsName, $userIdsName, $departmentsIdsName, $setIsForAllUsers = false)
	{
		if (!is_array($this->$assignmentsName))
		{
			return;
		}
		$userIds = [];
		$departmentsIds = [];
		foreach ($this->$assignmentsName as $codeId)
		{
			if (!is_string($codeId))
			{
				continue;
			}
			if ($setIsForAllUsers && $codeId === static::ALL_USERS)
			{
				$this->isForAllUsers = true;
			}
			elseif (EntityCodesHelper::isUser($codeId))
			{
				$userIds[EntityCodesHelper::getUserId($codeId)] = true;
			}
			elseif (EntityCodesHelper::isDepartment($codeId))
			{
				$departmentsIds[EntityCodesHelper::getDepartmentId($codeId)] = true;
			}
		}
		$this->$departmentsIdsName = array_unique(array_merge($this->$departmentsIdsName, array_keys($departmentsIds)));
		$this->$userIdsName = array_unique(array_merge($this->$userIdsName, array_keys($userIds)));
	}

	private function filterDuplicateId($values, $id)
	{
		return array_values(array_filter($values, function ($item) use ($id) {
			return $item != $id;
		}));
	}

	public function setSchedule($schedule)
	{
		$this->schedule = $schedule;
	}

	public function getSchedule()
	{
		return $this->schedule;
	}

	public function configureFilterRules()
	{
		return [
			(new Filter\Validator\NumberValidator('id'))
				->configureIntegerOnly(true)
				->configureMin(1)
			,
			(new Filter\Validator\StringValidator('name', 'reportPeriod', 'type'))
			,
			(new Filter\Modifier\StringModifier('name', 'reportPeriod', 'type'))
				->configureTrim(true)
				->configureSkipOnError(true)
			,
			(new Filter\Modifier\CallbackModifier('reportPeriodStartWeekDay'))
				->configureCallback(function ($value) {
					return (int)$value;
				})
			,
			(new Filter\Validator\RangeValidator('reportPeriodStartWeekDay'))
				->configureRange(ScheduleFormHelper::getReportPeriodWeekDaysValues())
				->configureStrict(true)
			,
			(new Filter\Validator\RangeValidator('reportPeriod'))
				->configureRange(ScheduleFormHelper::getReportPeriodsValues())
				->configureStrict(true)
			,
			(new Filter\Validator\RangeValidator('controlledActions'))
				->configureRange(ScheduleFormHelper::getControlledActionValues())
			,
			(new Filter\Validator\RangeValidator('type'))
				->configureRange(ScheduleFormHelper::getScheduleTypesValues())
				->configureStrict(true)
			,
			(new Filter\Validator\EachValidator('assignments', 'assignmentsExcluded'))
			,
			(new Filter\Validator\EachValidator('allowedDevices'))
				->configureValidator(
					(new Filter\Validator\RangeValidator())
						->configureRange([true, false, 'on', ''])
						->configureStrict(true)
				)
			,
			(new Filter\Modifier\CallbackModifier('allowedDevices'))
				->configureCallback(function ($values) {
					foreach ($values as $index => $item)
					{
						$values[$index] = $item === 'on' || $item === true ? true : false;
					}
					return $values;
				})
				->configureSkipOnError(true)
				->configureSkipOnArray(false)
			,
			(new Filter\Validator\EachValidator('userIds', 'departmentIds', 'userIdsExcluded', 'departmentIdsExcluded'))
				->configureValidator(
					(new Filter\Validator\NumberValidator())
						->configureMin(1)
						->configureIntegerOnly(true)
						->configureSkipOnEmpty(true)
				)
				->configureSkipOnEmpty(true)
			,
		];
	}
}