<?php
namespace Bitrix\Timeman\Form\Schedule;

use Bitrix\Main\Web\Json;
use Bitrix\Timeman\Model\Schedule\Calendar\Calendar;
use Bitrix\Timeman\Model\Schedule\Calendar\CalendarTable;
use Bitrix\Timeman\Util\Form\BaseForm;
use Bitrix\Timeman\Util\Form\Filter;

class CalendarForm extends BaseForm
{
	public $name;
	public $dates = [];
	public $calendarId;
	public $parentId;
	public $systemCode;
	public $datesJson;

	public function __construct($calendar = null)
	{
		$this->name = '';

		/** @var Calendar $calendar */
		if ($calendar)
		{
			$this->calendarId = $calendar->getId();
			$this->parentId = $calendar->getParentCalendarId();
			$this->dates = $calendar->obtainFinalExclusions();
			$this->systemCode = $calendar->getSystemCode();
		}
	}

	public function setDates($dates)
	{
		$this->dates = $dates;
	}

	protected function runAfterValidate()
	{
		$this->dates = [];
		if ($this->hasErrors())
		{
			return;
		}
		if (!is_null($this->datesJson) && $this->datesJson !== '')
		{
			try
			{
				$this->dates = Json::decode($this->datesJson);
			}
			catch (\Exception $exc)
			{
			}
		}
	}

	public function configureFilterRules()
	{
		return [
			(new Filter\Validator\CallbackValidator('datesJson'))
				->configureSkipOnEmpty(false)
				->configureCallback($this->getDatesJsonValidateCallback())
			,
			(new Filter\Modifier\StringModifier('name'))
				->configureTrim(true)
			,
			(new Filter\Validator\StringValidator('name'))
			,
			(new Filter\Validator\NumberValidator('parentId'))
				->configureIntegerOnly(true)
				->configureMin(0)
			,
			(new Filter\Validator\RangeValidator('systemCode'))
				->configureRange(CalendarTable::getAllSystemCodes())
				->configureStrict(true)
			,
			(new Filter\Validator\NumberValidator('calendarId'))
				->configureIntegerOnly(true)
				->configureMin(1),
		];
	}

	private function getDatesJsonValidateCallback(): callable
	{
		return function ($value)
		{
			if ($this->isEmptyValue($value))
			{
				return true;
			}
			if (is_array($value))
			{
				return false;
			}

			try
			{
				$decoded = Json::decode($value);
			}
			catch (\Exception $exc)
			{
				return false;
			}

			if (!is_array($decoded))
			{
				return false;
			}
			foreach ($decoded as $year => $months)
			{
				if (!is_numeric($year) || $year > 2500 || $year < 2000)
				{
					return false;
				}
				foreach ($months as $month => $days)
				{
					if (!is_numeric($month) || $month < 0 || $month > 11)
					{
						return false;
					}
					foreach ((array)$days as $day => $time)
					{
						if (!is_numeric($month) || $day < 0 || $day > 31)
						{
							return false;
						}
						if (!is_numeric($time))
						{
							return false;
						}
					}
				}
			}
			return true;
		};
	}

	private function isEmptyValue($value): bool
	{
		return (is_null($value) || $value === '');
	}
}