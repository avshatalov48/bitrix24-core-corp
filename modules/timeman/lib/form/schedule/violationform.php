<?php
namespace Bitrix\Timeman\Form\Schedule;

use Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Helper\EntityCodesHelper;
use Bitrix\Timeman\Helper\TimeDictionary;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Schedule\Violation\ViolationRules;
use Bitrix\Timeman\Model\Schedule\Violation\ViolationRulesTable;
use Bitrix\Timeman\Util\Form\BaseForm;
use Bitrix\Timeman\Util\Form\Filter;

Loc::loadMessages(__FILE__);

class ViolationForm extends BaseForm
{
	public $id;
	public $scheduleId;
	public $entityCode;

	public $maxExactStartFormatted;
	public $maxExactStart;
	public $minExactEndFormatted;
	public $minExactEnd;

	public $relativeStartFromFormatted;
	public $relativeStartFrom;
	public $relativeStartToFormatted;
	public $relativeStartTo;
	public $relativeEndFromFormatted;
	public $relativeEndFrom;
	public $relativeEndToFormatted;
	public $relativeEndTo;

	public $maxOffsetStartFormatted;
	public $maxOffsetStart;
	public $minOffsetEndFormatted;
	public $minOffsetEnd;

	public $minDayDurationFormatted;
	public $minDayDuration;

	public $maxAllowedToEditWorkTimeFormatted;
	public $maxAllowedToEditWorkTime;

	public $maxWorkTimeLackForPeriod;
	public $maxShiftStartDelayFormatted;


	public $maxShiftStartDelay;
	public $missedShiftStart;


	// field USERS_TO_NOTIFY divided by groups
	public $startEndNotifyUsers;
	public $hoursPerDayNotifyUsers;
	public $editWorktimeNotifyUsers;
	public $hoursPerPeriodNotifyUsers;
	public $shiftTimeNotifyUsers;
	public $shiftCheckNotifyUsers;

	// service fields, not intended to save to db
	public $saveAllViolationFormFields = false;
	public $saveStartEndViolations;

	public $useExactStartEndDay;
	public $useRelativeStartEndDay;
	public $useOffsetStartEndDay;

	public $saveHoursPerDayViolations;
	public $saveEditWorktimeViolations;
	public $saveHoursForPeriodViolations;
	public $saveShiftDelayViolations;

	/** @var TimeHelper */
	private $timeHelper;

	/**
	 * @param ViolationRules|null $violationRules
	 */
	public function __construct($violationRules = null)
	{
		$this->timeHelper = TimeHelper::getInstance();
		if (is_null($violationRules))
		{
			return;
		}
		$this->id = $violationRules->getId();
		$this->scheduleId = $violationRules->getScheduleId();
		$this->scheduleId = $violationRules->getScheduleId();
		$this->entityCode = $violationRules->getEntityCode();
		$this->minExactEnd = $violationRules->getMinExactEnd();
		$this->maxExactStart = $violationRules->getMaxExactStart();
		$this->minOffsetEnd = $violationRules->getMinOffsetEnd();
		$this->maxOffsetStart = $violationRules->getMaxOffsetStart();
		$this->relativeStartFrom = $violationRules->getRelativeStartFrom();
		$this->relativeStartTo = $violationRules->getRelativeStartTo();
		$this->relativeEndFrom = $violationRules->getRelativeEndFrom();
		$this->relativeEndTo = $violationRules->getRelativeEndTo();
		$this->minDayDuration = $violationRules->getMinDayDuration();
		$this->maxAllowedToEditWorkTime = $violationRules->getMaxAllowedToEditWorkTime();
		$this->maxWorkTimeLackForPeriod = $violationRules->getMaxWorkTimeLackForPeriod();
		$this->maxShiftStartDelay = $violationRules->getMaxShiftStartDelay();
		$this->missedShiftStart = $violationRules->getMissedShiftStart();

		$this->startEndNotifyUsers = $violationRules->getNotifyUsersSymbolic(ViolationRulesTable::USERS_TO_NOTIFY_FIXED_START_END);
		$this->hoursPerDayNotifyUsers = $violationRules->getNotifyUsersSymbolic(ViolationRulesTable::USERS_TO_NOTIFY_FIXED_RECORD_TIME_PER_DAY);
		$this->editWorktimeNotifyUsers = $violationRules->getNotifyUsersSymbolic(ViolationRulesTable::USERS_TO_NOTIFY_FIXED_EDIT_WORKTIME);
		$this->hoursPerPeriodNotifyUsers = $violationRules->getNotifyUsersSymbolic(ViolationRulesTable::USERS_TO_NOTIFY_FIXED_TIME_FOR_PERIOD);
		$this->shiftTimeNotifyUsers = $violationRules->getNotifyUsersSymbolic(ViolationRulesTable::USERS_TO_NOTIFY_SHIFT_DELAY);
		$this->shiftCheckNotifyUsers = $violationRules->getNotifyUsersSymbolic(ViolationRulesTable::USERS_TO_NOTIFY_SHIFT_MISSED_START);
	}

	protected function runAfterValidate()
	{
		$this->convertToSecondsIfNoErrors('maxExactStartFormatted', 'maxExactStart');
		$this->convertToSecondsIfNoErrors('minExactEndFormatted', 'minExactEnd');
		$this->convertToSecondsIfNoErrors('maxOffsetStartFormatted', 'maxOffsetStart');
		$this->convertToSecondsIfNoErrors('minOffsetEndFormatted', 'minOffsetEnd');
		$this->convertToSecondsIfNoErrors('relativeStartFromFormatted', 'relativeStartFrom');
		$this->convertToSecondsIfNoErrors('relativeStartToFormatted', 'relativeStartTo');
		$this->convertToSecondsIfNoErrors('relativeEndFromFormatted', 'relativeEndFrom');
		$this->convertToSecondsIfNoErrors('relativeEndToFormatted', 'relativeEndTo');

		$this->convertToSecondsIfNoErrors('minDayDurationFormatted', 'minDayDuration');
		$this->convertToSecondsIfNoErrors('maxAllowedToEditWorkTimeFormatted', 'maxAllowedToEditWorkTime');
		$this->convertToSecondsIfNoErrors('maxShiftStartDelayFormatted', 'maxShiftStartDelay');
	}

	private function convertFormattedTimeToSeconds($time)
	{
		return $time == '--:--' ? -1 : $this->timeHelper->convertHoursMinutesToSeconds($time);
	}

	public function configureFilterRules()
	{
		return [

			(new Filter\Modifier\CallbackModifier(
				'startEndNotifyUsers',
				'hoursPerDayNotifyUsers',
				'hoursPerPeriodNotifyUsers',
				'shiftTimeNotifyUsers',
				'editWorktimeNotifyUsers',
				'shiftCheckNotifyUsers'
			))
				->configureSkipOnArray(false)
				->configureCallback(function ($values) {
					if (!is_array($values))
					{
						return [];
					}
					$result = [];
					foreach ($values as $code)
					{
						if (!is_string($code))
						{
							continue;
						}
						if ($code === ViolationRulesTable::USERS_TO_NOTIFY_USER_MANAGER
							|| EntityCodesHelper::isUser($code)
						)
						{
							$result[] = $code;
						}
					}
					return $result;
				})
				->configureSkipOnError(true)
			,

			(new Filter\Validator\RangeValidator(
				'useRelativeStartEndDay', 'useExactStartEndDay', 'useOffsetStartEndDay',
				'saveHoursForPeriodViolations', 'saveHoursPerDayViolations',
				'saveStartEndViolations', 'saveShiftDelayViolations',
				'saveEditWorktimeViolations', 'missedShiftStart')
			)
				->configureRange(['on', 1, 0, ''])
				->configureStrict(true)
				->configureSkipOnEmpty(true)
			,
			(new Filter\Modifier\CallbackModifier('missedShiftStart'))
				->configureCallback(function ($value) {
					return $value === 'on' || $value === 1 ? ViolationRulesTable::MISSED_SHIFT_IS_TRACKED : ViolationRulesTable::MISSED_SHIFT_IS_NOT_TRACKED;
				})
				->configureSkipOnError(true)
			,
			(new Filter\Validator\NumberValidator('maxWorkTimeLackForPeriod'))
				->configureDefaultErrorMessage('TM_VIOLATION_FORM_ERROR_PERIOD_TIME_LACK_ERROR')
				->configureIntegerOnly(true)
				->configureMin(-1)
			,
			(new Filter\Modifier\CallbackModifier('maxWorkTimeLackForPeriod'))
				->configureSkipOnError(true)
				->configureSkipOnEmpty(true)
				->configureCallback(function ($value) {
					return $value * TimeDictionary::SECONDS_PER_HOUR;
				})
			,
			(new Filter\Validator\NumberValidator('scheduleId', 'id'))
				->configureIntegerOnly(true)
				->configureMin(1)
			,
			(new Filter\Validator\RegularExpressionValidator('entityCode'))
				->configurePattern("#[UD]{1}[R]{0,1}[1-9]+#")
			,
			(new Filter\Validator\StringValidator(
				'maxExactStartFormatted',
				'minExactEndFormatted',
				'relativeStartFromFormatted',
				'relativeStartToFormatted',
				'relativeEndFromFormatted',
				'relativeEndToFormatted',
				'minDayDurationFormatted',
				'minOffsetEndFormatted',
				'maxOffsetStartFormatted',
				'maxAllowedToEditWorkTimeFormatted',
				'maxShiftStartDelayFormatted'
			))
			,
			(new Filter\Validator\NumberValidator(
				'maxExactStart',
				'minExactEnd',
				'relativeStartFrom',
				'relativeStartTo',
				'relativeEndFrom',
				'relativeEndTo',
				'minOffsetEnd',
				'maxOffsetStart',
				'minDayDuration',
				'maxAllowedToEditWorkTime',
				'maxShiftStartDelay'
			))
				->configureIntegerOnly(true)
				->configureMin(-1)
			,
			(new Filter\Validator\RegularExpressionValidator(
				'maxExactStartFormatted',
				'minExactEndFormatted',
				'relativeStartFromFormatted',
				'relativeStartToFormatted',
				'relativeEndFromFormatted',
				'relativeEndToFormatted',
				'minDayDurationFormatted',
				'minOffsetEndFormatted',
				'maxOffsetStartFormatted',
				'maxAllowedToEditWorkTimeFormatted',
				'maxShiftStartDelayFormatted'
			))
				->configurePattern('#^([0-9]|0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]|--:--$#')
			,
			(new Filter\Modifier\CallbackModifier('maxWorkTimeLackForPeriod'))
				->configureDefaultErrorMessage('TM_VIOLATION_FORM_ERROR_PERIOD_TIME_LACK_ERROR')
				->configureCallback(function ($value) {
					return $value === '' || $value === null ? -1 : $value;
				})
				->configureSkipOnEmpty(false)
				->configureSkipOnError(true)
			,
		];
	}

	private function issetValue($value)
	{
		return $value !== null && $value !== -1 && $value !== '-1' && $value !== '--:--';
	}

	public function adjustViolationSeconds()
	{
		if ($this->issetValue($this->maxExactStart))
		{
			$this->maxExactStart = $this->maxExactStart + 59;
		}
		if ($this->issetValue($this->relativeStartTo))
		{
			$this->relativeStartTo = $this->relativeStartTo + 59;
		}
		if ($this->issetValue($this->relativeEndTo))
		{
			$this->relativeEndTo = $this->relativeEndTo + 59;
		}
		if ($this->issetValue($this->maxShiftStartDelay))
		{
			$this->maxShiftStartDelay = $this->maxShiftStartDelay + 59;
		}
	}

	private function isChecked($option)
	{
		return $option === 'on';
	}

	public function isSaveStartEndViolations()
	{
		return $this->isChecked($this->saveStartEndViolations);
	}

	public function isSaveEditWorktimeViolations()
	{
		return $this->isChecked($this->saveEditWorktimeViolations);
	}

	public function isSaveHoursPerDayViolations()
	{
		return $this->isChecked($this->saveHoursPerDayViolations);
	}

	public function isSaveHoursForPeriodViolations()
	{
		return $this->isChecked($this->saveHoursForPeriodViolations);
	}

	public function isSaveShiftDelayViolations()
	{
		return $this->isChecked($this->saveShiftDelayViolations);
	}

	public function isUseRelativeStartEndDay()
	{
		return $this->isChecked($this->useRelativeStartEndDay);
	}

	public function isUseOffsetStartEndDay()
	{
		return $this->isChecked($this->useOffsetStartEndDay);
	}

	public function isUseExactStartEndDay()
	{
		return $this->isChecked($this->useExactStartEndDay);
	}

	public function showStartEndViolations()
	{
		return
			$this->isValueSet($this->maxOffsetStart) ||
			$this->isValueSet($this->minOffsetEnd) ||
			$this->isValueSet($this->maxExactStart) ||
			$this->isValueSet($this->minExactEnd) ||
			$this->isValueSet($this->relativeStartFrom) ||
			$this->isValueSet($this->relativeStartTo) ||
			$this->isValueSet($this->relativeEndFrom) ||
			$this->isValueSet($this->relativeEndTo);
	}

	public function showViolationContainer($isShifted)
	{
		return $this->showFixedViolations($isShifted) || $this->showShiftViolations($isShifted);
	}

	public function showFixedViolations($isShifted)
	{
		if ($isShifted)
		{
			return false;
		}
		return $this->showStartEndViolations()
			   || $this->showHoursPerDayViolations()
			   || $this->showEditWorktimeViolations()
			   || $this->showHoursForPeriodViolations();
	}

	public function showShiftViolations($isShifted)
	{
		return $isShifted
			   && (
				   $this->isValueSet($this->maxShiftStartDelay)
				   || $this->showEditWorktimeViolations()
				   || $this->missedShiftStart > 0
				   || !empty($this->shiftCheckNotifyUsers)
			   );
	}

	public function showHoursPerDayViolations()
	{
		return $this->isValueSet($this->minDayDuration);
	}

	public function showEditWorktimeViolations()
	{
		return $this->isValueSet($this->maxAllowedToEditWorkTime);
	}

	private function isValueSet($value)
	{
		return !is_null($value) && $value >= 0;
	}

	public function showExactStartEndDay()
	{
		return $this->isValueSet($this->minExactEnd) ||
			   $this->isValueSet($this->maxExactStart);
	}

	public function showOffsetStartEndDay()
	{
		return $this->isValueSet($this->minOffsetEnd) ||
			   $this->isValueSet($this->maxOffsetStart);
	}

	public function showRelativeStartEndDay()
	{
		return $this->isValueSet($this->relativeStartFrom) ||
			   $this->isValueSet($this->relativeStartTo) ||
			   $this->isValueSet($this->relativeEndFrom) ||
			   $this->isValueSet($this->relativeEndTo);
	}

	public function showHoursForPeriodViolations()
	{
		return $this->isValueSet($this->maxWorkTimeLackForPeriod);
	}

	private function getTimeOrDefault($value, $withPostfix = false)
	{
		return $this->isValueSet($value) ?
			($withPostfix ? $this->timeHelper->convertSecondsToHoursMinutesAmPm($value) : $this->timeHelper->convertSecondsToHoursMinutes($value))
			: '--:--';
	}

	public function getFormattedMaxExactStart()
	{
		return $this->getTimeOrDefault($this->maxExactStart, true);
	}

	public function getFormattedMaxOffsetStart()
	{
		return $this->getTimeOrDefault($this->maxOffsetStart, false);
	}

	public function getFormattedMinOffsetEnd()
	{
		return $this->getTimeOrDefault($this->minOffsetEnd, false);
	}

	public function getFormattedMinExactEnd()
	{
		return $this->getTimeOrDefault($this->minExactEnd, true);
	}

	public function getFormattedRelativeStartFrom()
	{
		return $this->getTimeOrDefault($this->relativeStartFrom, true);
	}

	public function getFormattedRelativeStartTo()
	{
		return $this->getTimeOrDefault($this->relativeStartTo, true);
	}

	public function getFormattedRelativeEndFrom()
	{
		return $this->getTimeOrDefault($this->relativeEndFrom, true);
	}

	public function getFormattedRelativeEndTo()
	{
		return $this->getTimeOrDefault($this->relativeEndTo, true);
	}

	public function getFormattedMinDayDuration()
	{
		return $this->getTimeOrDefault($this->minDayDuration);
	}

	public function getFormattedMaxAllowedToEditWorkTime()
	{
		return $this->getTimeOrDefault($this->maxAllowedToEditWorkTime);
	}

	public function getFormattedMaxWorkTimeLackForPeriod()
	{
		return $this->isValueSet($this->maxWorkTimeLackForPeriod) ? $this->maxWorkTimeLackForPeriod / TimeDictionary::SECONDS_PER_HOUR : '';
	}

	public function getFormattedMaxShiftStartDelay()
	{
		return $this->getTimeOrDefault($this->maxShiftStartDelay, false);
	}

	public function showShiftDelayViolations()
	{
		return $this->isValueSet($this->maxShiftStartDelay);
	}

	public function showShiftStartViolations()
	{
		return $this->missedShiftStart > 0;
	}

	/**
	 * Reset fields of form by selected UI checkboxes
	 */
	public function resetExtraFields($isShifted, $controlStartOnly)
	{
		if ($isShifted)
		{
			$this->resetFixedScheduleViolations();
		}
		else
		{
			$this->resetShiftScheduleViolations();
		}
		$this->resetUncheckedShiftViolations();
		$this->resetUncheckedFixedViolations();
		$this->resetEndTimeViolations($controlStartOnly);
	}

	private function resetEndTimeViolations($controlStartOnly)
	{
		if ($controlStartOnly)
		{
			$this->relativeEndFrom = -1;
			$this->relativeEndTo = -1;
			$this->minExactEnd = -1;
		}
	}

	private function resetUncheckedFixedViolations()
	{
		if (!$this->isSaveStartEndViolations())
		{
			$this->resetFixedStartEndViolations();
			$this->startEndNotifyUsers = [];
		}
		else
		{
			if (!$this->isUseExactStartEndDay())
			{
				$this->resetFixedExactViolations();
			}
			if (!$this->isUseRelativeStartEndDay())
			{
				$this->resetFixedRelativeViolations();
			}
			if (!$this->isUseOffsetStartEndDay())
			{
				$this->resetFixedOffsetViolations();
			}
		}

		if (!$this->isSaveHoursPerDayViolations())
		{
			$this->minDayDuration = -1;
			$this->hoursPerDayNotifyUsers = [];
		}
		if (!$this->isSaveEditWorktimeViolations())
		{
			$this->maxAllowedToEditWorkTime = -1;
			$this->editWorktimeNotifyUsers = [];
		}
		if (!$this->isSaveHoursForPeriodViolations())
		{
			$this->maxWorkTimeLackForPeriod = -1;
			$this->hoursPerPeriodNotifyUsers = [];
		}
	}

	private function resetUncheckedShiftViolations()
	{
		if (!$this->isSaveShiftDelayViolations())
		{
			$this->maxShiftStartDelay = -1;
			$this->shiftTimeNotifyUsers = [];
		}
		if (!$this->isSaveEditWorktimeViolations())
		{
			$this->maxAllowedToEditWorkTime = -1;
			$this->editWorktimeNotifyUsers = [];
		}
		if (!$this->missedShiftStart)
		{
			$this->shiftCheckNotifyUsers = [];
		}
	}

	private function resetShiftScheduleViolations()
	{
		$this->maxShiftStartDelay = -1;
		$this->missedShiftStart = 0;

		$this->shiftCheckNotifyUsers = [];
		$this->shiftTimeNotifyUsers = [];
	}

	private function resetFixedOffsetViolations()
	{
		$this->minOffsetEnd = -1;
		$this->maxOffsetStart = -1;
	}

	private function resetFixedRelativeViolations()
	{
		$this->relativeStartFrom = -1;
		$this->relativeStartTo = -1;
		$this->relativeEndFrom = -1;
		$this->relativeEndTo = -1;
	}

	private function resetFixedExactViolations()
	{
		$this->maxExactStart = -1;
		$this->minExactEnd = -1;
	}

	private function resetFixedStartEndViolations()
	{
		$this->resetFixedExactViolations();
		$this->resetFixedRelativeViolations();
		$this->resetFixedOffsetViolations();
	}

	private function resetFixedScheduleViolations()
	{
		$this->resetFixedStartEndViolations();
		$this->minDayDuration = -1;
		$this->maxWorkTimeLackForPeriod = -1;

		$this->hoursPerDayNotifyUsers = [];
		$this->hoursPerPeriodNotifyUsers = [];
		$this->startEndNotifyUsers = [];
	}

	private function convertToSecondsIfNoErrors($fieldName, $saveToName)
	{
		if ($this->issetValue($this->$saveToName))
		{
			return;
		}
		if (!$this->hasErrors($fieldName))
		{
			$this->$saveToName = $this->convertFormattedTimeToSeconds($this->$fieldName);
		}
		else
		{
			$this->$saveToName = -1;
		}
	}
}