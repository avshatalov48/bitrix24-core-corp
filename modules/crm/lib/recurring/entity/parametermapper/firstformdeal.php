<?php
namespace Bitrix\Crm\Recurring\Entity\ParameterMapper;

use \Bitrix\Crm\Recurring\Manager,
	\Bitrix\Crm\Recurring\Calculator;

class FirstFormDeal extends DealMap
{
	/** @var FirstFormDeal */
	protected static $instance = null;

	const FIELD_MODE_NAME = 'EXECUTION_TYPE';
	const FIELD_SINGLE_TYPE_NAME = 'DEAL_TYPE_BEFORE';
	const FIELD_SINGLE_INTERVAL_NAME = 'DEAL_COUNT_BEFORE';
	const FIELD_MULTIPLE_TYPE_NAME = 'PERIOD_DEAL';

	protected function getScheme()
	{
		return [
			self::FIELD_MODE => self::FIELD_MODE_NAME,
			self::FIELD_SINGLE_TYPE => self::FIELD_SINGLE_TYPE_NAME,
			self::FIELD_SINGLE_INTERVAL => self::FIELD_SINGLE_INTERVAL_NAME,
			self::FIELD_MULTIPLE_TYPE => self::FIELD_MULTIPLE_TYPE_NAME
		];
	}

	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new FirstFormDeal();
		}
		return self::$instance;
	}

	public function fillMap(array $params = [])
	{
		$singleType =
		$multiplyType = Calculator::SALE_TYPE_DAY_OFFSET;
		$singleValue =
		$multiplyValue = 0;

		$this->mode = (int)$params[self::FIELD_MODE_NAME];
		if ($this->mode === Manager::SINGLE_EXECUTION)
		{
			$singleType =
			$this->unitType = (int)$params[self::FIELD_SINGLE_TYPE_NAME];

			$singleValue =
			$this->interval = (int)$params[self::FIELD_SINGLE_INTERVAL_NAME];
		}
		else
		{
			$multiplyType =
			$this->unitType = (int)$params[self::FIELD_MULTIPLE_TYPE_NAME];

			$multiplyValue =
			$this->interval = 1;
		}

		$this->map = [
			self::FIELD_MODE => $this->mode,
			self::FIELD_SINGLE_TYPE => $singleType,
			self::FIELD_SINGLE_INTERVAL => $singleValue,
			self::FIELD_MULTIPLE_TYPE => $multiplyType,
			self::FIELD_MULTIPLE_INTERVAL => $multiplyValue,
		];
	}
}
