<?php
namespace Bitrix\Timeman\Form\Schedule;

use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\ScheduleTable;
use Bitrix\Timeman\Util\Form\BaseForm;
use Bitrix\Timeman\Util\Form\Filter;

class WorktimeRestrictionsForm extends BaseForm
{
	public $maxShiftStartOffset;
	public $maxShiftStartOffsetFormatted;
	public $allowedToReopenRecord;
	public $allowedToEditRecord;

	/**
	 * @param Schedule|null $schedule
	 */
	public function __construct($schedule = null)
	{
		if (!($schedule instanceof Schedule))
		{
			return;
		}
		$this->maxShiftStartOffset = $schedule->obtainWorktimeRestrictions(ScheduleTable::WORKTIME_RESTRICTION_MAX_SHIFT_START_OFFSET);
		$this->maxShiftStartOffsetFormatted = TimeHelper::getInstance()->convertSecondsToHoursMinutes($this->maxShiftStartOffset);
	}

	protected function runAfterValidate()
	{
		$this->convertToSecondsIfNoErrors('maxShiftStartOffsetFormatted', 'maxShiftStartOffset');
	}

	public function configureFilterRules()
	{
		return [
			(new Filter\Validator\LoadableValidator('allowedToReopenRecord', 'allowedToEditRecord'))
			,
			(new Filter\Modifier\CallbackModifier('maxShiftStartOffsetFormatted'))
				->configureCallback(function ($value) {
					return $value === '--:--' ? null : $value;
				})
			,
			(new Filter\Validator\RegularExpressionValidator('maxShiftStartOffsetFormatted'))
				->configurePattern(TimeHelper::getInstance()->getTimeRegExp($ignoreAmPmMode = true))
			,
			(new Filter\Validator\NumberValidator('maxShiftStartOffset'))
				->configureIntegerOnly(true)
				->configureMin(-1)
			,
		];
	}

	private function convertFormattedTimeToSeconds($time)
	{
		return $time == '--:--' ? -1 : TimeHelper::getInstance()->convertHoursMinutesToSeconds($time);
	}

	private function issetValue($value)
	{
		return $value !== null && $value !== -1 && $value !== '-1' && $value !== '--:--';
	}

	private function isValueSet($value)
	{
		return !is_null($value) && $value >= 0;
	}

	private function getTimeOrDefault($value, $withPostfix = false)
	{
		return $this->isValueSet($value) ?
			($withPostfix ? TimeHelper::getInstance()->convertSecondsToHoursMinutesAmPm($value) : TimeHelper::getInstance()->convertSecondsToHoursMinutes($value))
			: '01:00';
	}

	public function getFormattedMaxShiftStartOffset()
	{
		return $this->getTimeOrDefault($this->maxShiftStartOffset, false);
	}

	private function convertToSecondsIfNoErrors($fieldFromName, $saveToName)
	{
		if ($this->issetValue($this->$saveToName) || $this->$fieldFromName === null)
		{
			return;
		}
		if (!$this->hasErrors($fieldFromName))
		{
			$this->$saveToName = $this->convertFormattedTimeToSeconds($this->$fieldFromName);
		}
		else
		{
			$this->$saveToName = -1;
		}
	}
}