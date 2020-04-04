<?php
namespace Bitrix\Crm\Recurring\Entity\ParameterMapper;

use \Bitrix\Crm\Recurring\DateType,
	\Bitrix\Crm\Recurring\Calculator,
	\Bitrix\Main\Type\Date;

class FirstFormInvoice extends InvoiceMap
{
	/** @var FirstFormInvoice */
	protected static $instance = null;

	const FIELD_PERIOD_NAME = 'PERIOD';
	const FIELD_INTERVAL_NAME = 'INTERVAL';
	const FIELD_DAILY_INTERVAL_DAY_NAME = 'DAILY_INTERVAL_DAY';
	const FIELD_DAILY_WORKDAY_ONLY_NAME = 'DAILY_WORKDAY_ONLY';
	const FIELD_DAILY_TYPE_NAME = 'DAILY_TYPE';
	const FIELD_WEEKLY_WEEKDAYS_NAME = 'WEEKLY_WEEK_DAYS';
	const FIELD_WEEKLY_INTERVAL_WEEK_NAME = 'WEEKLY_INTERVAL_WEEK';
	const FIELD_WEEKLY_TYPE_NAME = 'WEEKLY_TYPE';
	const FIELD_MONTHLY_FIRST_TYPE_INTERVAL_DAY_NAME = 'MONTHLY_INTERVAL_DAY';
	const FIELD_MONTHLY_FIRST_TYPE_INTERVAL_NAME = 'MONTHLY_MONTH_NUM_1';
	const FIELD_MONTHLY_FIRST_TYPE_WORKDAY_ONLY_NAME = 'MONTHLY_WORKDAY_ONLY';
	const FIELD_MONTHLY_SECOND_TYPE_WEEK_VALUE_NAME = 'MONTHLY_WEEKDAY_NUM';
	const FIELD_MONTHLY_SECOND_TYPE_INTERVAL_NAME = 'MONTHLY_MONTH_NUM_2';
	const FIELD_MONTHLY_SECOND_TYPE_WEEKDAY_NAME = 'MONTHLY_WEEK_DAY';
	const FIELD_MONTHLY_TYPE_NAME = 'MONTHLY_TYPE';
	const FIELD_YEARLY_FIRST_TYPE_INTERVAL_DAY_NAME = 'YEARLY_INTERVAL_DAY';
	const FIELD_YEARLY_FIRST_TYPE_WORKDAY_ONLY_NAME = 'YEARLY_WORKDAY_ONLY';
	const FIELD_YEARLY_FIRST_TYPE_INTERVAL_MONTH_NAME = 'YEARLY_MONTH_NUM_1';
	const FIELD_YEARLY_SECOND_TYPE_WEEK_VALUE_NAME = 'YEARLY_WEEK_DAY_NUM';
	const FIELD_YEARLY_SECOND_TYPE_INTERVAL_MONTH_NAME = 'YEARLY_MONTH_NUM_2';
	const FIELD_YEARLY_SECOND_TYPE_WEEKDAY_NAME = 'YEARLY_WEEK_DAY';
	const FIELD_YEARLY_TYPE_NAME = 'YEARLY_TYPE';
	const FIELD_DATE_PAY_BEFORE_TYPE_NAME = 'DATE_PAY_BEFORE_TYPE';
	const FIELD_DATE_PAY_BEFORE_PERIOD_NAME = 'DATE_PAY_BEFORE_PERIOD';
	const FIELD_DATE_PAY_BEFORE_OFFSET_NAME = 'DATE_PAY_BEFORE_COUNT';
	const FIELD_IS_ALLOWED_TO_SEND_BILL_NAME = 'RECURRING_EMAIL_SEND';
	const FIELD_EMAIL_ID_NAME = 'RECURRING_EMAIL_ID';
	const FIELD_EMAIL_TEMPLATE_ID_NAME = 'EMAIL_TEMPLATE_ID';

	protected function getScheme()
	{
		return [
			self::FIELD_PERIOD => self::FIELD_PERIOD_NAME,
			self::FIELD_INTERVAL => self::FIELD_INTERVAL_NAME,
			self::FIELD_DAILY_INTERVAL => self::FIELD_DAILY_INTERVAL_DAY_NAME,
			self::FIELD_DAILY_WORKDAY_ONLY => self::FIELD_DAILY_WORKDAY_ONLY_NAME,
			self::FIELD_DAILY_TYPE => self::FIELD_DAILY_TYPE_NAME,
			self::FIELD_WEEKLY_WEEKDAYS => self::FIELD_WEEKLY_WEEKDAYS_NAME,
			self::FIELD_WEEKLY_INTERVAL => self::FIELD_WEEKLY_INTERVAL_WEEK_NAME,
			self::FIELD_WEEKLY_TYPE => self::FIELD_WEEKLY_TYPE_NAME,
			self::FIELD_MONTHLY_FIRST_TYPE_INTERVAL_DAY => self::FIELD_MONTHLY_FIRST_TYPE_INTERVAL_DAY_NAME,
			self::FIELD_MONTHLY_FIRST_TYPE_INTERVAL => self::FIELD_MONTHLY_FIRST_TYPE_INTERVAL_NAME,
			self::FIELD_MONTHLY_FIRST_TYPE_WORKDAY_ONLY => self::FIELD_MONTHLY_FIRST_TYPE_WORKDAY_ONLY_NAME,
			self::FIELD_MONTHLY_SECOND_TYPE_WEEK_VALUE => self::FIELD_MONTHLY_SECOND_TYPE_WEEK_VALUE_NAME,
			self::FIELD_MONTHLY_SECOND_TYPE_INTERVAL => self::FIELD_MONTHLY_SECOND_TYPE_INTERVAL_NAME,
			self::FIELD_MONTHLY_SECOND_TYPE_WEEKDAY => self::FIELD_MONTHLY_SECOND_TYPE_WEEKDAY_NAME,
			self::FIELD_MONTHLY_TYPE => self::FIELD_MONTHLY_TYPE_NAME,
			self::FIELD_YEARLY_FIRST_TYPE_INTERVAL_DAY => self::FIELD_YEARLY_FIRST_TYPE_INTERVAL_DAY_NAME,
			self::FIELD_YEARLY_FIRST_TYPE_WORKDAY_ONLY => self::FIELD_YEARLY_FIRST_TYPE_WORKDAY_ONLY_NAME,
			self::FIELD_YEARLY_FIRST_TYPE_INTERVAL_MONTH => self::FIELD_YEARLY_FIRST_TYPE_INTERVAL_MONTH_NAME,
			self::FIELD_YEARLY_SECOND_TYPE_WEEK_VALUE => self::FIELD_YEARLY_SECOND_TYPE_WEEK_VALUE_NAME,
			self::FIELD_YEARLY_SECOND_TYPE_INTERVAL_MONTH => self::FIELD_YEARLY_SECOND_TYPE_INTERVAL_MONTH_NAME,
			self::FIELD_YEARLY_SECOND_TYPE_WEEKDAY => self::FIELD_YEARLY_SECOND_TYPE_WEEKDAY_NAME,
			self::FIELD_YEARLY_TYPE => self::FIELD_YEARLY_TYPE_NAME,
			self::FIELD_DATE_PAY_BEFORE_TYPE => self::FIELD_DATE_PAY_BEFORE_TYPE_NAME,
			self::FIELD_DATE_PAY_BEFORE_PERIOD => self::FIELD_DATE_PAY_BEFORE_PERIOD_NAME,
			self::FIELD_DATE_PAY_BEFORE_OFFSET => self::FIELD_DATE_PAY_BEFORE_OFFSET_NAME,
			self::FIELD_IS_ALLOWED_TO_SEND_BILL => self::FIELD_IS_ALLOWED_TO_SEND_BILL_NAME,
			self::FIELD_EMAIL_ID => self::FIELD_EMAIL_ID_NAME,
			self::FIELD_EMAIL_TEMPLATE_ID => self::FIELD_EMAIL_TEMPLATE_ID_NAME,
		];
	}

	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new FirstFormInvoice();
		}
		return self::$instance;
	}

	public function fillMap(array $params = [])
	{
		$scheme = $this->getScheme();
		foreach ($scheme as $code => $fieldName)
		{
			if (
				$fieldName === self::FIELD_DAILY_WORKDAY_ONLY_NAME
				|| $fieldName === self::FIELD_MONTHLY_FIRST_TYPE_WORKDAY_ONLY_NAME
				|| $fieldName === self::FIELD_YEARLY_FIRST_TYPE_WORKDAY_ONLY_NAME
			)
			{
				$this->map[$code] = ($params[$fieldName] === 'Y') ? 'Y' : 'N';
			}
			elseif ($fieldName === self::FIELD_WEEKLY_WEEKDAYS_NAME)
			{
				$this->map[$code] = is_array($params[$fieldName]) ? $params[$fieldName] : [];
			}
			else
			{
				$item = (int)$params[$fieldName];
				$this->map[$code] = ($item > 0) ? $item : 0;
			}
		}

		if (empty($this->map[self::FIELD_DAILY_TYPE]))
		{
			$this->map[self::FIELD_DAILY_TYPE] = DateType\Day::TYPE_ALTERNATING_DAYS;
		}
		if (empty($this->map[self::FIELD_WEEKLY_TYPE]))
		{
			$this->map[self::FIELD_WEEKLY_TYPE] = DateType\Week::TYPE_ALTERNATING_WEEKDAYS;
		}

		$this->mode = $this->map[self::FIELD_PERIOD];
		$this->unitType = $this->map[$this->getUnitTypeMapCode()];
		$this->interval = $this->map[$this->getIntervalMapCode()];
	}

	public function checkMatchingDate(Date $date)
	{
		switch ($this->mode)
		{
			case Calculator::SALE_TYPE_DAY_OFFSET:
				return true;
				break;

			case Calculator::SALE_TYPE_WEEK_OFFSET:
				$weekdays = $this->map[self::FIELD_WEEKLY_WEEKDAYS];
				if (is_array($weekdays))
				{
					return in_array($date->format('N'), $weekdays);
				}
				break;

			case Calculator::SALE_TYPE_MONTH_OFFSET:
				if ($this->unitType === DateType\Month::TYPE_DAY_OF_ALTERNATING_MONTHS)
				{
					return (int)$date->format('j') === (int)$this->map[self::FIELD_MONTHLY_FIRST_TYPE_INTERVAL_DAY];
				}
				elseif ($this->unitType === DateType\Month::TYPE_WEEKDAY_OF_ALTERNATING_MONTHS)
				{
					if ((int)$date->format('N') !== (int)$this->map[self::FIELD_MONTHLY_SECOND_TYPE_WEEKDAY])
					{
						return false;
					}

					if ($this->map[self::FIELD_MONTHLY_SECOND_TYPE_WEEK_VALUE] === DateType\Month::LAST_WEEK_IN_MONTH_VALUE)
					{
						$currentMonth = $date->format('n');
						$date->add('1 week');
						return $currentMonth !== $date->format('n');
					}
					else
					{
						$weekValue = (int)(floor($date->format('j') / 7));
						return $weekValue === (int)$this->map[self::FIELD_MONTHLY_SECOND_TYPE_WEEK_VALUE];
					}
				}
				else
				{
					return true;
				}
				break;

			case Calculator::SALE_TYPE_YEAR_OFFSET:
				if ($this->unitType === DateType\Year::TYPE_DAY_OF_CERTAIN_MONTH)
				{
					if ((int)$date->format('j') !== (int)$this->map[self::FIELD_YEARLY_FIRST_TYPE_INTERVAL_DAY])
					{
						return false;
					}

					return (int)$date->format('n') === (int)$this->map[self::FIELD_YEARLY_FIRST_TYPE_INTERVAL_MONTH];
				}
				elseif ($this->unitType === DateType\Year::TYPE_WEEKDAY_OF_CERTAIN_MONTH)
				{
					if ((int)$date->format('N') !== (int)$this->map[self::FIELD_YEARLY_SECOND_TYPE_WEEKDAY])
					{
						return false;
					}

					if ((int)$date->format('n') !== (int)$this->map[self::FIELD_YEARLY_SECOND_TYPE_INTERVAL_MONTH])
					{
						return false;
					}

					if ((int)$this->map[self::FIELD_YEARLY_SECOND_TYPE_WEEK_VALUE] === DateType\Month::LAST_WEEK_IN_MONTH_VALUE)
					{
						$currentMonth = $date->format('n');
						$date->add('1 week');
						return $currentMonth !== $date->format('n');
					}
					else
					{
						$weekValue = (int)(floor($date->format('j') / 7));
						return ($weekValue === (int)$this->map[self::FIELD_YEARLY_SECOND_TYPE_WEEK_VALUE]);
					}
				}
				else
				{
					return true;
				}
				break;
		}

		return false;
	}
}
