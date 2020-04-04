<?php
namespace Bitrix\Crm\Recurring;

use Bitrix\Main;
use Bitrix\Main\Type\Date;

class Calculator
{
	const SALE_TYPE_NON_ACTIVE_DATE = 'N';
	const SALE_TYPE_DAY_OFFSET = 1;
	const SALE_TYPE_WEEK_OFFSET = 2;
	const SALE_TYPE_MONTH_OFFSET = 3;
	const SALE_TYPE_YEAR_OFFSET = 4;
	const SALE_TYPE_CUSTOM_OFFSET = 5;

	const FIELD_PERIOD_NAME = 'PERIOD';

	const SALE_NAME_DAY_OFFSET = 'day';
	const SALE_NAME_WEEK_OFFSET = 'week';
	const SALE_NAME_MONTH_OFFSET = 'month';
	const SALE_NAME_YEAR_OFFSET = 'year';

	private static $instance = null;
	private $params = [];
	private $startDate = null;

	/**
	 * @return Calculator
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new Calculator();
		}
		return self::$instance;
	}

	/**
	 * Calculator constructor.
	 */
	public function __construct()
	{
		$this->startDate = new Date();
	}

	/**
	 * @param Date $date
	 */
	public function setStartDate(Date $date)
	{
		$this->startDate = $date;
	}

	/**
	 * @param array $params
	 */
	public function setParams(array $params = [])
	{
		$this->params = $params;
	}

	/**
	 * @return Date
	 */
	public function calculateDate()
	{
		$period = $this->params[self::FIELD_PERIOD_NAME];
		$startDate = clone($this->startDate);
		switch($period)
		{
			case static::SALE_TYPE_DAY_OFFSET:
				return DateType\Day::calculateDate($this->params, $startDate);
			case static::SALE_TYPE_WEEK_OFFSET:
				return DateType\Week::calculateDate($this->params, $startDate);
			case static::SALE_TYPE_MONTH_OFFSET:
				return DateType\Month::calculateDate($this->params, $startDate);
			case static::SALE_TYPE_YEAR_OFFSET:
				return DateType\Year::calculateDate($this->params, $startDate);
			default:
				return null;
		}
	}

	/**
	 * @param array $params
	 * @param Date $startDate
	 *
	 * @return Date
	 * @deprecated
	 */
	public static function getNextDate(array $params, Date $startDate = null)
	{
		if (empty($params))
			return null;
		if (is_null($startDate))
		{
			$startDate = new Date();
		}

		if ($params['PREPARE_PARAMS_CALCULATION'] !== 'N')
			$params = static::prepareCalculationDate($params);

		$instance = self::getInstance();
		$instance->setParams($params);
		$instance->setStartDate($startDate);
		return $instance->calculateDate();
	}

	/**
	 * @param $value
	 *
	 * @return int|null
	 */
	public static function resolveTypeId($value)
	{
		switch ($value)
		{
			case self::SALE_NAME_DAY_OFFSET:
				return self::SALE_TYPE_DAY_OFFSET;
			case self::SALE_NAME_WEEK_OFFSET:
				return self::SALE_TYPE_WEEK_OFFSET;
			case self::SALE_NAME_MONTH_OFFSET:
				return self::SALE_TYPE_MONTH_OFFSET;
			case self::SALE_NAME_YEAR_OFFSET:
				return self::SALE_TYPE_YEAR_OFFSET;
		}
		$value = (int)$value;

		return self::isAllowedTypeId($value) ? $value : null;
	}

	/**
	 * @param $value
	 *
	 * @return string|null
	 */
	public static function resolveTypeName($value)
	{
		switch ($value)
		{
			case self::SALE_TYPE_DAY_OFFSET:
				return self::SALE_NAME_DAY_OFFSET;
			case self::SALE_TYPE_WEEK_OFFSET:
				return self::SALE_NAME_WEEK_OFFSET;
			case self::SALE_TYPE_MONTH_OFFSET:
				return self::SALE_NAME_MONTH_OFFSET;
			case self::SALE_TYPE_YEAR_OFFSET:
				return self::SALE_NAME_YEAR_OFFSET;
		}

		return null;
	}

	private static function isAllowedTypeId($typeId)
	{
		$typeId = (int)$typeId;
		return $typeId >= self::SALE_TYPE_DAY_OFFSET && $typeId <= self::SALE_TYPE_CUSTOM_OFFSET;
	}

	/**
	 * @param array $params
	 *
	 * @return array
	 * @deprecated
	 */
	public static function prepareCalculationDate(array $params)
	{
		$result = array(
			"PERIOD" => (int)$params[self::FIELD_PERIOD_NAME] ? (int)$params[self::FIELD_PERIOD_NAME] : null
		);

		if (isset($params['PERIOD_DEAL']) && (int)$params['EXECUTION_TYPE'] === Manager::MULTIPLY_EXECUTION)
		{
			$result[self::FIELD_PERIOD_NAME] = (int)$params['PERIOD_DEAL'];

			switch($result[self::FIELD_PERIOD_NAME])
			{
				case self::SALE_TYPE_DAY_OFFSET:
				{
					$params['DAILY_INTERVAL_DAY'] = 2;
					break;
				}
				case self::SALE_TYPE_WEEK_OFFSET:
				{
					$result[self::FIELD_PERIOD_NAME] = self::SALE_TYPE_DAY_OFFSET;
					$params['DAILY_INTERVAL_DAY'] = 8;
					break;
				}
				case self::SALE_TYPE_MONTH_OFFSET:
				{
					$params['MONTHLY_MONTH_NUM_1'] = 2;
					$params['MONTHLY_INTERVAL_DAY'] = date('j');
					$params['MONTHLY_TYPE'] = DateType\Month::TYPE_DAY_OF_ALTERNATING_MONTHS;
					break;
				}
				case self::SALE_TYPE_YEAR_OFFSET:
				{
					$params['YEARLY_TYPE'] = DateType\Year::TYPE_ALTERNATING_YEAR;
					$params['INTERVAL_YEARLY'] = 2;
					break;
				}
			}
		}
		elseif (isset($params['DEAL_TYPE_BEFORE']) && (int)$params['EXECUTION_TYPE'] === Manager::SINGLE_EXECUTION)
		{
			$result[self::FIELD_PERIOD_NAME] = (int)$params['DEAL_TYPE_BEFORE'];

			switch($result[self::FIELD_PERIOD_NAME])
			{
				case self::SALE_TYPE_DAY_OFFSET:
				{
					$params['DAILY_TYPE'] = DateType\Day::TYPE_A_FEW_DAYS_BEFORE;
					$params['DAILY_INTERVAL_DAY'] = (int)$params['DEAL_COUNT_BEFORE'];
					break;
				}
				case self::SALE_TYPE_WEEK_OFFSET:
				{
					$params['WEEKLY_TYPE'] = DateType\Week::TYPE_A_FEW_WEEKS_BEFORE;
					$params['WEEKLY_INTERVAL_WEEK'] = (int)$params['DEAL_COUNT_BEFORE'];
					break;
				}
				case self::SALE_TYPE_MONTH_OFFSET:
				{
					$params['MONTHLY_TYPE'] = DateType\Month::TYPE_A_FEW_MONTHS_BEFORE;
					$result['INTERVAL_MONTH'] = (int)$params['DEAL_COUNT_BEFORE'];
					break;
				}
			}
		}

		switch($result[self::FIELD_PERIOD_NAME])
		{
			case static::SALE_TYPE_DAY_OFFSET:
				$result['INTERVAL_DAY'] = $params['DAILY_INTERVAL_DAY'];
				$result['IS_WORKDAY'] = $params['DAILY_WORKDAY_ONLY'];
				if (empty($params['DAILY_TYPE']))
				{
					$params['DAILY_TYPE'] = DateType\Day::TYPE_ALTERNATING_DAYS;
				}
				$result['TYPE'] = $params['DAILY_TYPE'];
				break;
			case static::SALE_TYPE_WEEK_OFFSET:
				
				$result['WEEKDAYS'] = $params['WEEKLY_WEEK_DAYS'];
				$result['INTERVAL_WEEK'] = $params['WEEKLY_INTERVAL_WEEK'];
				if (!isset($params['WEEKLY_TYPE']))
				{
					$params['WEEKLY_TYPE'] = DateType\Week::TYPE_ALTERNATING_WEEKDAYS;
				}
				$result['TYPE'] = $params['WEEKLY_TYPE'];
				break;
			case static::SALE_TYPE_MONTH_OFFSET:
				$result['INTERVAL_DAY'] = $params['MONTHLY_INTERVAL_DAY'];
				if ((int)$params['MONTHLY_TYPE'] === DateType\Month::TYPE_DAY_OF_ALTERNATING_MONTHS)
				{
					$result['INTERVAL_MONTH'] = $params['MONTHLY_MONTH_NUM_1'];
					$result['IS_WORKDAY'] = $params['MONTHLY_WORKDAY_ONLY'];
				}
				elseif ((int)$params['MONTHLY_TYPE'] === DateType\Month::TYPE_WEEKDAY_OF_ALTERNATING_MONTHS)
				{
					$result['INTERVAL_WEEK'] = $params['MONTHLY_WEEKDAY_NUM'];
					$result['INTERVAL_MONTH'] = $params['MONTHLY_MONTH_NUM_2'];
					$result['WEEKDAY'] = $params['MONTHLY_WEEK_DAY'];
				}
				$result['TYPE'] = $params['MONTHLY_TYPE'];
				break;
			case static::SALE_TYPE_YEAR_OFFSET:
				$result['INTERVAL_DAY'] = $params['YEARLY_INTERVAL_DAY'];

				if ((int)$params['YEARLY_TYPE'] === DateType\Year::TYPE_DAY_OF_CERTAIN_MONTH)
				{
					$result['INTERVAL_DAY'] = $params['YEARLY_INTERVAL_DAY'];
					$result['INTERVAL_MONTH'] = $params['YEARLY_MONTH_NUM_1'];
					$result['IS_WORKDAY'] = $params['YEARLY_WORKDAY_ONLY'];
				}
				elseif ((int)$params['YEARLY_TYPE'] === DateType\Year::TYPE_WEEKDAY_OF_CERTAIN_MONTH)
				{
					$result['INTERVAL_WEEK'] = $params['YEARLY_WEEK_DAY_NUM'];
					$result['INTERVAL_MONTH'] = $params['YEARLY_MONTH_NUM_2'];
					$result['WEEKDAY'] = $params['YEARLY_WEEK_DAY'];
				}
				$result['TYPE'] = (int)$params['YEARLY_TYPE'];
		}
		
		return $result;
	}
}