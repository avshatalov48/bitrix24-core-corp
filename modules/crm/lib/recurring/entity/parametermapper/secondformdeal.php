<?php
namespace Bitrix\Crm\Recurring\Entity\ParameterMapper;

use Bitrix\Crm\Recurring\Calculator;
use Bitrix\Crm\Recurring\Manager;

class SecondFormDeal extends DealMap
{
	/** @var SecondFormDeal */
	protected static $instance = null;

	const FIELD_MODE_NAME = 'MODE';
	const FIELD_SINGLE_TYPE_NAME = 'SINGLE_TYPE';
	const FIELD_SINGLE_INTERVAL_NAME = 'SINGLE_INTERVAL_VALUE';
	const FIELD_MULTIPLE_TYPE_NAME = 'MULTIPLE_TYPE';
	const FIELD_MULTIPLE_CUSTOM_TYPE_NAME = 'MULTIPLE_CUSTOM_TYPE';
	const FIELD_MULTIPLE_CUSTOM_INTERVAL_NAME = 'MULTIPLE_CUSTOM_INTERVAL_VALUE';
	const FIELD_BEGINDATE_TYPE_NAME = 'BEGINDATE_TYPE';
	const FIELD_BEGINDATE_OFFSET_TYPE_NAME = 'OFFSET_BEGINDATE_TYPE';
	const FIELD_BEGINDATE_OFFSET_VALUE_NAME = 'OFFSET_BEGINDATE_VALUE';
	const FIELD_CLOSEDATE_TYPE_NAME = 'CLOSEDATE_TYPE';
	const FIELD_CLOSEDATE_OFFSET_TYPE_NAME = 'OFFSET_CLOSEDATE_TYPE';
	const FIELD_CLOSEDATE_OFFSET_VALUE_NAME = 'OFFSET_CLOSEDATE_VALUE';

	protected function getScheme()
	{
		return [
			self::FIELD_MODE => self::FIELD_MODE_NAME,
			self::FIELD_SINGLE_TYPE => self::FIELD_SINGLE_TYPE_NAME,
			self::FIELD_SINGLE_INTERVAL => self::FIELD_SINGLE_INTERVAL_NAME,
			self::FIELD_MULTIPLE_TYPE => self::FIELD_MULTIPLE_TYPE_NAME,
			self::FIELD_MULTIPLE_CUSTOM_TYPE => self::FIELD_MULTIPLE_CUSTOM_TYPE_NAME,
			self::FIELD_MULTIPLE_CUSTOM_INTERVAL => self::FIELD_MULTIPLE_CUSTOM_INTERVAL_NAME,
			self::FIELD_BEGINDATE_TYPE => self::FIELD_BEGINDATE_TYPE_NAME,
			self::FIELD_BEGINDATE_OFFSET_TYPE => self::FIELD_BEGINDATE_OFFSET_TYPE_NAME,
			self::FIELD_BEGINDATE_OFFSET_VALUE => self::FIELD_BEGINDATE_OFFSET_VALUE_NAME,
			self::FIELD_CLOSEDATE_TYPE => self::FIELD_CLOSEDATE_TYPE_NAME,
			self::FIELD_CLOSEDATE_OFFSET_TYPE => self::FIELD_CLOSEDATE_OFFSET_TYPE_NAME,
			self::FIELD_CLOSEDATE_OFFSET_VALUE => self::FIELD_CLOSEDATE_OFFSET_VALUE_NAME,
		];
	}

	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new SecondFormDeal();
		}
		return self::$instance;
	}

	public function fillMap(array $params = [])
	{
		$this->mode = (int)$params[self::FIELD_MODE_NAME];
		if ($this->mode === Manager::SINGLE_EXECUTION)
		{
			$this->unitType = (int)$params[self::FIELD_SINGLE_TYPE_NAME];
			$this->interval = (int)$params[self::FIELD_SINGLE_INTERVAL_NAME];
		}
		elseif ($this->mode === Manager::MULTIPLY_EXECUTION)
		{
			$this->unitType = (int)$params[self::FIELD_MULTIPLE_TYPE_NAME];
			$this->interval = 1;
			if ($this->unitType === Calculator::SALE_TYPE_CUSTOM_OFFSET)
			{
				$this->unitType = (int)$params[self::FIELD_MULTIPLE_CUSTOM_TYPE_NAME];
				$this->interval = (int)$params[self::FIELD_MULTIPLE_CUSTOM_INTERVAL_NAME];
			}
		}

		$scheme = $this->getScheme();
		foreach ($scheme as $code => $fieldName)
		{
			$item = (int)($params[$fieldName] ?? 0);
			
			$this->map[$code] = ($item > 0) ? $item : 0;
		}
	}
}
