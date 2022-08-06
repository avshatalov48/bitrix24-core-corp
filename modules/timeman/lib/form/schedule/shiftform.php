<?php
namespace Bitrix\Timeman\Form\Schedule;

use Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Schedule\Shift\Shift;
use Bitrix\Timeman\Util\Form\BaseForm;
use Bitrix\Timeman\Util\Form\Filter;

Loc::loadMessages(__FILE__);

class ShiftForm extends BaseForm
{
	public $shiftId;
	public $scheduleId;
	public $name;
	public $breakDurationFormatted;
	public $breakDuration;
	public $startTimeFormatted;
	public $startTime;
	public $endTimeFormatted;
	public $endTime;
	public $workDays;

	/** @var TimeHelper */
	private $timeHelper;

	public function __construct(?Shift $shift = null)
	{
		$this->timeHelper = TimeHelper::getInstance();
		if ($shift)
		{
			$this->name = $shift->getName();
			$this->breakDuration = $shift->getBreakDuration();
			$this->startTime = $shift->getWorkTimeStart();
			$this->endTime = $shift->getWorkTimeEnd();
			$this->workDays = $shift->getWorkDays();
			$this->shiftId = $shift->getId();
		}
	}

	protected function runAfterValidate()
	{
		$this->breakDuration = $this->convertToSecondsIfNoErrors('breakDurationFormatted');
		$this->startTime = $this->convertToSecondsIfNoErrors('startTimeFormatted');
		$this->endTime = $this->convertToSecondsIfNoErrors('endTimeFormatted');
	}

	private function convertToSecondsIfNoErrors($fieldName)
	{
		if (!$this->hasErrors($fieldName))
		{
			return $this->timeHelper->convertHoursMinutesToSeconds($this->$fieldName);
		}
		return 0;
	}

	public function configureFilterRules()
	{
		$maxError = 'TM_SHIFT_FORM_NUMBER_TOO_BIG_ERROR';
		$minError = 'TM_SHIFT_FORM_NUMBER_LESS_MIN_ERROR';
		$intError = 'TM_SHIFT_FORM_NUMBER_INTEGER_ONLY_ERROR';

		return [
			(new Filter\Validator\RegularExpressionValidator('breakDurationFormatted', 'startTimeFormatted', 'endTimeFormatted'))
				->configureDefaultErrorMessage('TM_SHIFT_FORM_TIME_FORMATTED_ERROR')
				->configurePattern($this->timeHelper->getTimeRegExp())
			,
			(new Filter\Modifier\StringModifier('name', 'workDays'))
				->configureTrim(true)
			,
			(new Filter\Validator\StringValidator('name', 'workDays'))
				->configureDefaultErrorMessage('TM_SHIFT_FORM_NAME_ERROR')
			,
			(new Filter\Validator\NumberValidator('shiftId', 'scheduleId', 'breakDuration', 'startTime', 'endTime'))
				->configureDefaultErrorMessage('TM_SHIFT_FORM_NUMBER_INTEGER_ONLY_ERROR')
				->configureMin(0, $minError)
				->configureIntegerOnly(true, $intError)
			,
			(new Filter\Validator\NumberValidator('workDays'))
				->configureMin(0, $minError)
				->configureMax(1234567, $maxError)
				->configureIntegerOnly(true, $intError)
			,
		];
	}

	public function getFieldLabels()
	{
		return [
			'breakDurationFormatted' => Loc::getMessage('TM_SHIFT_FORM_BREAK_DURATION_TITLE'),
			'startTimeFormatted' => Loc::getMessage('TM_SHIFT_FORM_START_TIME_TITLE'),
			'endTimeFormatted' => Loc::getMessage('TM_SHIFT_FORM_END_TIME_TITLE'),
			'shiftId' => Loc::getMessage('TM_SHIFT_FORM_SHIFT_ID_TITLE'),
			'scheduleId' => Loc::getMessage('TM_SHIFT_FORM_SCHEDULE_ID_TITLE'),
			'name' => Loc::getMessage('TM_SHIFT_FORM_NAME_TITLE'),
			'breakDuration' => Loc::getMessage('TM_SHIFT_FORM_BREAK_DURATION_TITLE'),
			'startTime' => Loc::getMessage('TM_SHIFT_FORM_START_TIME_TITLE'),
			'endTime' => Loc::getMessage('TM_SHIFT_FORM_END_TIME_TITLE'),
		];
	}

	public function hasWorkDays(): bool
	{
		return $this->workDays !== '';
	}

	public function getFormattedStartTime($defaultStartTime = 9 * 60 * 60)
	{
		return $this->timeHelper->convertSecondsToHoursMinutesAmPm($this->startTime ?? $defaultStartTime);
	}

	public function getFormattedEndTime($defaultEndTime = 18 * 60 * 60)
	{
		return $this->timeHelper->convertSecondsToHoursMinutesAmPm($this->endTime ?? $defaultEndTime);
	}

	public function getFormattedBreakDuration($defaultBreakDuration = 1 * 60 * 60)
	{
		return $this->timeHelper->convertSecondsToHoursMinutes(
			$this->breakDuration === null
				? $defaultBreakDuration
				: $this->breakDuration
		);
	}
}