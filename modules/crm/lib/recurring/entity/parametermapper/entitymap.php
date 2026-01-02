<?php
namespace Bitrix\Crm\Recurring\Entity\ParameterMapper;

use Bitrix\Crm\Recurring\Calculator;
use Bitrix\Crm\Recurring\DateType;
use Bitrix\Crm\Recurring\Manager;

abstract class EntityMap extends Map
{
	public const FIELD_MODE = 0;
	public const FIELD_MULTIPLE_TYPE = 1;
	public const FIELD_MULTIPLE_INTERVAL = 2;
	public const FIELD_MULTIPLE_CUSTOM_TYPE = 3;
	public const FIELD_MULTIPLE_CUSTOM_INTERVAL = 4;
	public const FIELD_SINGLE_TYPE = 5;
	public const FIELD_SINGLE_INTERVAL = 6;
	public const FIELD_BEGINDATE_TYPE = 7;
	public const FIELD_BEGINDATE_OFFSET_TYPE = 8;
	public const FIELD_BEGINDATE_OFFSET_VALUE = 9;
	public const FIELD_CLOSEDATE_TYPE = 10;
	public const FIELD_CLOSEDATE_OFFSET_TYPE = 11;
	public const FIELD_CLOSEDATE_OFFSET_VALUE = 12;

	/**
	 * @return array
	 */
	public function getPreparedMap(): array
	{
		$intervalNames = [
			Calculator::SALE_TYPE_DAY_OFFSET => DateType\Day::FIELD_INTERVAL_NAME,
			Calculator::SALE_TYPE_WEEK_OFFSET => DateType\Week::FIELD_INTERVAL_NAME,
			Calculator::SALE_TYPE_MONTH_OFFSET => DateType\Month::FIELD_INTERVAL_NAME,
			Calculator::SALE_TYPE_YEAR_OFFSET => DateType\Year::FIELD_INTERVAL_NAME,
		];

		$intervalName = $intervalNames[$this->unitType];

		return [
			Calculator::FIELD_PERIOD_NAME => $this->unitType,
			DateType\Base::FIELD_TYPE_NAME => $this->getType(),
			$intervalName => $this->interval,
		];
	}

	/**
	 * @return int
	 */
	private function getType(): ?int
	{
		if ($this->mode === Manager::MULTIPLY_EXECUTION)
		{
			$multipleTypes = [
				Calculator::SALE_TYPE_DAY_OFFSET => DateType\Day::TYPE_A_FEW_DAYS_AFTER,
				Calculator::SALE_TYPE_WEEK_OFFSET => DateType\Week::TYPE_A_FEW_WEEKS_AFTER,
				Calculator::SALE_TYPE_MONTH_OFFSET => DateType\Month::TYPE_A_FEW_MONTHS_AFTER,
				Calculator::SALE_TYPE_YEAR_OFFSET => DateType\Year::TYPE_ALTERNATING_YEAR,
			];

			return $multipleTypes[$this->unitType] ?? null;
		}

		$singleTypes = [
			Calculator::SALE_TYPE_DAY_OFFSET => DateType\Day::TYPE_A_FEW_DAYS_BEFORE,
			Calculator::SALE_TYPE_WEEK_OFFSET => DateType\Week::TYPE_A_FEW_WEEKS_BEFORE,
			Calculator::SALE_TYPE_MONTH_OFFSET => DateType\Month::TYPE_A_FEW_MONTHS_BEFORE,
		];

		return $singleTypes[$this->unitType] ?? null;
	}
}
