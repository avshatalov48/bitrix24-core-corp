<?php
namespace Bitrix\Crm\Recurring\Entity\ParameterMapper;

use Bitrix\Crm\Recurring\Calculator,
	Bitrix\Crm\Recurring\DateType;

abstract class InvoiceMap extends Map
{
	const FIELD_PERIOD = 0;
	const FIELD_TYPE = 1;
	const FIELD_INTERVAL = 2;
	const FIELD_IS_WORKING_ONLY = 3;
	/** Day calculation fields */
	const FIELD_DAILY_INTERVAL = 21;
	const FIELD_DAILY_WORKDAY_ONLY = 22;
	const FIELD_DAILY_TYPE = 23;
	/** Week calculation fields */
	const FIELD_WEEKLY_WEEKDAYS = 31;
	const FIELD_WEEKLY_INTERVAL = 32;
	const FIELD_WEEKLY_TYPE = 33;
	/** Month calculation fields */
	const FIELD_MONTHLY_TYPE = 40;
	const FIELD_MONTHLY_FIRST_TYPE_INTERVAL_DAY = 41;
	const FIELD_MONTHLY_FIRST_TYPE_INTERVAL = 42;
	const FIELD_MONTHLY_FIRST_TYPE_WORKDAY_ONLY = 43;
	const FIELD_MONTHLY_SECOND_TYPE_WEEK_VALUE = 45;
	const FIELD_MONTHLY_SECOND_TYPE_INTERVAL = 46;
	const FIELD_MONTHLY_SECOND_TYPE_WEEKDAY = 47;
	/** Year calculation fields */
	const FIELD_YEARLY_TYPE = 50;
	const FIELD_YEARLY_FIRST_TYPE_INTERVAL_DAY = 51;
	const FIELD_YEARLY_FIRST_TYPE_WORKDAY_ONLY = 52;
	const FIELD_YEARLY_FIRST_TYPE_INTERVAL_MONTH = 53;
	const FIELD_YEARLY_SECOND_TYPE_WEEK_VALUE = 55;
	const FIELD_YEARLY_SECOND_TYPE_INTERVAL_MONTH = 56;
	const FIELD_YEARLY_SECOND_TYPE_WEEKDAY = 57;
	/** Another calculation fields */
	const FIELD_DATE_PAY_BEFORE_TYPE = 61;
	const FIELD_DATE_PAY_BEFORE_PERIOD = 62;
	const FIELD_DATE_PAY_BEFORE_OFFSET = 63;
	/** Additional fields */
	const FIELD_IS_ALLOWED_TO_SEND_BILL = 70;
	const FIELD_EMAIL_ID = 71;
	const FIELD_EMAIL_TEMPLATE_ID = 72;

	/**
	 * @return array
	 */
	public function getPreparedMap()
	{
		$result = [
			Calculator::FIELD_PERIOD_NAME => $this->mode,
			DateType\Base::FIELD_TYPE_NAME => $this->unitType
		];

		$fields = [];
		switch($this->mode)
		{
			case Calculator::SALE_TYPE_DAY_OFFSET:
				$fields = $this->getDayFields();
				break;
			case Calculator::SALE_TYPE_WEEK_OFFSET:
				$fields = $this->getWeekFields();
				break;
			case Calculator::SALE_TYPE_MONTH_OFFSET:
				$fields = $this->getMonthFields();
				break;
			case Calculator::SALE_TYPE_YEAR_OFFSET:
				$fields = $this->getYearFields();
		}

		$result = array_merge($result, $fields);

		return $result;
	}

	/**
	 * @return array
	 */
	private function getDayFields()
	{
		return [
			DateType\Day::FIELD_INTERVAL_NAME => $this->interval,
			DateType\Day::FIELD_IS_WORKDAY_NAME => $this->map[self::FIELD_DAILY_WORKDAY_ONLY]
		];
	}

	/**
	 * @return array
	 */
	private function getWeekFields()
	{
		return [
			DateType\Week::FIELD_INTERVAL_NAME => $this->interval,
			DateType\Week::FIELD_WEEKDAYS_NAME => $this->map[self::FIELD_WEEKLY_WEEKDAYS]
		];
	}

	/**
	 * @return array
	 */
	private function getMonthFields()
	{
		if ($this->unitType === DateType\Month::TYPE_DAY_OF_ALTERNATING_MONTHS)
		{
			$result = [
				DateType\Day::FIELD_INTERVAL_NAME => $this->map[self::FIELD_MONTHLY_FIRST_TYPE_INTERVAL_DAY],
				DateType\Day::FIELD_IS_WORKDAY_NAME => $this->map[self::FIELD_MONTHLY_FIRST_TYPE_WORKDAY_ONLY]
			];
		}
		else
		{
			$result = [
				DateType\Week::FIELD_INTERVAL_NAME => $this->map[self::FIELD_MONTHLY_SECOND_TYPE_WEEK_VALUE],
				DateType\Month::FIELD_WEEKDAY_NAME => $this->map[self::FIELD_MONTHLY_SECOND_TYPE_WEEKDAY]
			];
		}

		$result[DateType\Month::FIELD_INTERVAL_NAME] = $this->interval;
		return $result;
	}

	/**
	 * @return array
	 */
	private function getYearFields()
	{
		if ($this->unitType === DateType\Year::TYPE_DAY_OF_CERTAIN_MONTH)
		{
			$result = [
				DateType\Day::FIELD_INTERVAL_NAME => $this->map[self::FIELD_YEARLY_FIRST_TYPE_INTERVAL_DAY],
				DateType\Day::FIELD_IS_WORKDAY_NAME => $this->map[self::FIELD_YEARLY_FIRST_TYPE_WORKDAY_ONLY]
			];
		}
		else
		{
			$result = [
				DateType\Week::FIELD_INTERVAL_NAME => $this->map[self::FIELD_YEARLY_SECOND_TYPE_WEEK_VALUE],
				DateType\Month::FIELD_WEEKDAY_NAME => $this->map[self::FIELD_YEARLY_SECOND_TYPE_WEEKDAY]
			];
		}

		$result[DateType\Month::FIELD_INTERVAL_NAME] = $this->interval;
		return $result;
	}

	protected function getUnitTypeMapCode()
	{
		$unitTypeCodes = [
			Calculator::SALE_TYPE_DAY_OFFSET => self::FIELD_DAILY_TYPE,
			Calculator::SALE_TYPE_WEEK_OFFSET => self::FIELD_WEEKLY_TYPE,
			Calculator::SALE_TYPE_MONTH_OFFSET => self::FIELD_MONTHLY_TYPE,
			Calculator::SALE_TYPE_YEAR_OFFSET => self::FIELD_YEARLY_TYPE,
		];

		return $unitTypeCodes[$this->mode];
	}

	protected function getIntervalMapCode()
	{
		$intervalCodes = [
			Calculator::SALE_TYPE_DAY_OFFSET => [
				DateType\Day::TYPE_ALTERNATING_DAYS => self::FIELD_DAILY_INTERVAL,
				DateType\Day::TYPE_A_FEW_DAYS_AFTER => self::FIELD_INTERVAL,
			],
			Calculator::SALE_TYPE_WEEK_OFFSET => [
				DateType\Day::TYPE_ALTERNATING_DAYS => self::FIELD_WEEKLY_INTERVAL,
				DateType\Week::TYPE_A_FEW_WEEKS_AFTER => self::FIELD_INTERVAL,
			],
			Calculator::SALE_TYPE_MONTH_OFFSET => [
				DateType\Month::TYPE_DAY_OF_ALTERNATING_MONTHS => self::FIELD_MONTHLY_FIRST_TYPE_INTERVAL,
				DateType\Month::TYPE_WEEKDAY_OF_ALTERNATING_MONTHS => self::FIELD_MONTHLY_SECOND_TYPE_INTERVAL,
				DateType\Month::TYPE_A_FEW_MONTHS_AFTER => self::FIELD_INTERVAL,
			],
			Calculator::SALE_TYPE_YEAR_OFFSET => [
				DateType\Year::TYPE_DAY_OF_CERTAIN_MONTH => self::FIELD_YEARLY_FIRST_TYPE_INTERVAL_MONTH,
				DateType\Year::TYPE_WEEKDAY_OF_CERTAIN_MONTH => self::FIELD_YEARLY_SECOND_TYPE_INTERVAL_MONTH,
				DateType\Year::TYPE_ALTERNATING_YEAR => self::FIELD_INTERVAL,
			],
		];

		return $intervalCodes[$this->mode][$this->unitType];
	}
}
