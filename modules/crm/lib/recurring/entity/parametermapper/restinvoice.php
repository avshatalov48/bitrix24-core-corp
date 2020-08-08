<?php
namespace Bitrix\Crm\Recurring\Entity\ParameterMapper;

use \Bitrix\Crm\Recurring\DateType,
	\Bitrix\Crm\Recurring\Calculator,
	\Bitrix\Crm\Recurring\Entity\Invoice,
	\Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class RestInvoice extends InvoiceMap
{
	/** @var RestInvoice $instance */
	protected static $instance = null;
	private $weekDays = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];

	const FIELD_WEEKDAY = 100;
	const FIELD_NUM_DAY_IN_MONTH = 101;
	const FIELD_NUM_WEEKDAY_IN_MONTH = 102;
	const FIELD_YEARLY_INTERVAL_MONTH = 103;

	const FIELD_PERIOD_NAME = 'PERIOD';
	const FIELD_TYPE_NAME = 'TYPE';
	const FIELD_INTERVAL_NAME = 'INTERVAL';
	const FIELD_IS_WORKING_ONLY_NAME = 'IS_WORKING_ONLY';
	const FIELD_WEEKDAY_NAME = 'WEEKDAY';
	const FIELD_NUM_DAY_IN_MONTH_NAME = 'NUM_DAY_IN_MONTH';
	const FIELD_NUM_WEEKDAY_IN_MONTH_NAME = 'NUM_WEEKDAY_IN_MONTH';
	const FIELD_YEARLY_INTERVAL_MONTH_NAME = 'NUM_MONTH_IN_YEAR';
	const FIELD_DATE_PAY_BEFORE_PERIOD_NAME = 'DATE_PAY_BEFORE_OFFSET_TYPE';
	const FIELD_DATE_PAY_BEFORE_OFFSET_NAME = 'DATE_PAY_BEFORE_OFFSET_VALUE';

	protected function getScheme()
	{
		return [
			self::FIELD_PERIOD => self::FIELD_PERIOD_NAME,
			self::FIELD_TYPE => self::FIELD_TYPE_NAME,
			self::FIELD_INTERVAL => self::FIELD_INTERVAL_NAME,
			self::FIELD_IS_WORKING_ONLY => self::FIELD_IS_WORKING_ONLY_NAME,
			self::FIELD_WEEKDAY => self::FIELD_WEEKDAY_NAME,
			self::FIELD_NUM_DAY_IN_MONTH => self::FIELD_NUM_DAY_IN_MONTH_NAME,
			self::FIELD_NUM_WEEKDAY_IN_MONTH => self::FIELD_NUM_WEEKDAY_IN_MONTH_NAME,
			self::FIELD_YEARLY_INTERVAL_MONTH => self::FIELD_YEARLY_INTERVAL_MONTH_NAME,
			self::FIELD_DATE_PAY_BEFORE_PERIOD => self::FIELD_DATE_PAY_BEFORE_PERIOD_NAME,
			self::FIELD_DATE_PAY_BEFORE_OFFSET => self::FIELD_DATE_PAY_BEFORE_OFFSET_NAME,
		];
	}

	public function getFieldsInfo()
	{
		$scheme = $this->getScheme();
		$fields = [];
		foreach ($scheme as $code => $item)
		{
			$fields[$item] = [
				'CAPTION' => Loc::getMessage("CRM_REST_INVOICE_PARAMETERS_{$item}_FIELD")
			];
			switch ($code)
			{
				case self::FIELD_IS_WORKING_ONLY:
					$fields[$item]['TYPE'] = 'char';
					break;
				case self::FIELD_PERIOD:
				case self::FIELD_WEEKDAY:
				case self::FIELD_DATE_PAY_BEFORE_PERIOD_NAME:
					$fields[$item]['TYPE'] = 'string';
					break;
				default:
					$fields[$item]['TYPE'] = 'integer';
			}
		}

		return $fields;
	}

	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new RestInvoice();
		}
		return self::$instance;
	}

	public function fillMap(array $params = [])
	{
		$scheme = $this->getScheme();
		foreach ($scheme as $code => $fieldName)
		{
			if ($fieldName === self::FIELD_PERIOD_NAME || $fieldName === self::FIELD_DATE_PAY_BEFORE_PERIOD_NAME)
			{
				$periodName = mb_strtolower($params[$fieldName]);
				$this->map[$code] = (int)Calculator::resolveTypeId($periodName);
			}
			elseif ($fieldName === self::FIELD_IS_WORKING_ONLY_NAME)
			{
				$item = $params[$fieldName];
				$this->map[$code] = !empty($this->weekDays[$item]) ? ($this->weekDays[$item] + 1) : (int)$item;
			}
			elseif ($fieldName === self::FIELD_IS_WORKING_ONLY_NAME)
			{
				$this->map[$code] = ($params[$fieldName] === 'Y') ? 'Y' : 'N';
			}
			else
			{
				$item = (int)$params[$fieldName];
				$this->map[$code] = ($item > 0) ? $item : 0;
			}
		}

		$this->mode = $this->map[self::FIELD_PERIOD];
		$this->unitType = $this->map[self::FIELD_TYPE];
		if ($this->mode === Calculator::SALE_TYPE_DAY_OFFSET)
		{
			$this->unitType = DateType\Day::TYPE_ALTERNATING_DAYS;
		}
		elseif ($this->mode === Calculator::SALE_TYPE_WEEK_OFFSET)
		{
			$this->unitType = DateType\Week::TYPE_ALTERNATING_WEEKDAYS;
		}

		$this->interval = $this->map[self::FIELD_INTERVAL];
		if ($this->mode === Calculator::SALE_TYPE_YEAR_OFFSET)
		{
			$this->interval = $this->map[self::FIELD_YEARLY_INTERVAL_MONTH];
		}

		$this->fillCompatibleFields();
	}

	private function fillCompatibleFields()
	{
		$this->map[self::FIELD_DATE_PAY_BEFORE_TYPE] = Invoice::UNSET_DATE_PAY_BEFORE;
		if (!empty($this->map[self::FIELD_DATE_PAY_BEFORE_PERIOD]))
		{
			$this->map[self::FIELD_DATE_PAY_BEFORE_TYPE] = Invoice::SET_DATE_PAY_BEFORE;
		}

		$this->map[$this->getUnitTypeMapCode()] = $this->unitType;
		$this->map[$this->getIntervalMapCode()] = $this->interval;
		if ($this->mode === Calculator::SALE_TYPE_DAY_OFFSET)
		{
			$this->map[self::FIELD_DAILY_WORKDAY_ONLY] = $this->map[self::FIELD_IS_WORKING_ONLY];
		}
		if ($this->mode === Calculator::SALE_TYPE_WEEK_OFFSET)
		{
			$weekday = 1;
			if ((int)$this->map[self::FIELD_WEEKDAY] > 0 && (int)$this->map[self::FIELD_WEEKDAY] <= 7)
			{
				$weekday = $this->map[self::FIELD_WEEKDAY];
			}
			$this->map[self::FIELD_WEEKLY_WEEKDAYS] = [$weekday];
		}
		elseif ($this->mode === Calculator::SALE_TYPE_MONTH_OFFSET || $this->mode === Calculator::SALE_TYPE_YEAR_OFFSET)
		{
			$dayCode = ($this->mode === Calculator::SALE_TYPE_MONTH_OFFSET) ? self::FIELD_MONTHLY_FIRST_TYPE_INTERVAL_DAY : self::FIELD_YEARLY_FIRST_TYPE_INTERVAL_DAY;
			$this->map[$dayCode] = $this->map[self::FIELD_NUM_DAY_IN_MONTH];

			$isWorkdayCode = ($this->mode === Calculator::SALE_TYPE_MONTH_OFFSET) ? self::FIELD_MONTHLY_FIRST_TYPE_WORKDAY_ONLY : self::FIELD_YEARLY_FIRST_TYPE_WORKDAY_ONLY;
			$this->map[$isWorkdayCode] = $this->map[self::FIELD_IS_WORKING_ONLY];

			$numWeekdayCode = ($this->mode === Calculator::SALE_TYPE_MONTH_OFFSET) ? self::FIELD_MONTHLY_SECOND_TYPE_WEEK_VALUE : self::FIELD_YEARLY_SECOND_TYPE_WEEK_VALUE;
			$this->map[$numWeekdayCode] = $this->map[self::FIELD_NUM_WEEKDAY_IN_MONTH];

			$weekdayCode = ($this->mode === Calculator::SALE_TYPE_MONTH_OFFSET) ? self::FIELD_MONTHLY_SECOND_TYPE_WEEKDAY : self::FIELD_YEARLY_SECOND_TYPE_WEEKDAY;
			$this->map[$weekdayCode] = $this->map[self::FIELD_WEEKDAY];
		}
	}

	public function convert(Map $map)
	{
		parent::convert($map);
		$this->map[self::FIELD_INTERVAL] = $this->interval;
		$this->map[self::FIELD_TYPE] = $this->unitType;
		switch($this->mode)
		{
			case Calculator::SALE_TYPE_DAY_OFFSET:
				$this->map[self::FIELD_IS_WORKING_ONLY] = $this->map[self::FIELD_DAILY_WORKDAY_ONLY];
				break;
			case Calculator::SALE_TYPE_WEEK_OFFSET:
				$weekdays = $this->map[self::FIELD_WEEKLY_WEEKDAYS];
				if (!empty($weekdays) && is_array($weekdays))
				{
					$this->map[self::FIELD_WEEKDAY] = (int)$weekdays[0];
				}
				break;
			case Calculator::SALE_TYPE_MONTH_OFFSET:
				$this->map[self::FIELD_NUM_DAY_IN_MONTH] = $this->map[self::FIELD_MONTHLY_FIRST_TYPE_INTERVAL_DAY];
				$this->map[self::FIELD_IS_WORKING_ONLY] = $this->map[self::FIELD_MONTHLY_FIRST_TYPE_WORKDAY_ONLY];
				$this->map[self::FIELD_NUM_WEEKDAY_IN_MONTH] = $this->map[self::FIELD_MONTHLY_SECOND_TYPE_WEEK_VALUE];
				$this->map[self::FIELD_WEEKDAY] = $this->map[self::FIELD_MONTHLY_SECOND_TYPE_WEEKDAY];
				break;
			case Calculator::SALE_TYPE_YEAR_OFFSET:
				$this->map[self::FIELD_YEARLY_INTERVAL_MONTH] = $this->interval;
				$this->map[self::FIELD_NUM_DAY_IN_MONTH] = $this->map[self::FIELD_YEARLY_FIRST_TYPE_INTERVAL_DAY];
				$this->map[self::FIELD_IS_WORKING_ONLY] = $this->map[self::FIELD_YEARLY_FIRST_TYPE_WORKDAY_ONLY];
				$this->map[self::FIELD_NUM_WEEKDAY_IN_MONTH] = $this->map[self::FIELD_YEARLY_SECOND_TYPE_WEEK_VALUE];
				$this->map[self::FIELD_WEEKDAY] = $this->map[self::FIELD_YEARLY_SECOND_TYPE_WEEKDAY];
				break;
		}
	}

	public function getFormattedMap()
	{
		$result = parent::getFormattedMap();
		$result[self::FIELD_PERIOD_NAME] = Calculator::resolveTypeName($result[self::FIELD_PERIOD_NAME]);
		$result[self::FIELD_DATE_PAY_BEFORE_PERIOD_NAME] = Calculator::resolveTypeName($result[self::FIELD_DATE_PAY_BEFORE_PERIOD_NAME]);
		if (!empty($result[self::FIELD_WEEKDAY_NAME]))
		{
			$result[self::FIELD_WEEKDAY_NAME] = $this->weekDays[(int)$result[self::FIELD_WEEKDAY_NAME] - 1];
		}
		return $result;
	}
}
