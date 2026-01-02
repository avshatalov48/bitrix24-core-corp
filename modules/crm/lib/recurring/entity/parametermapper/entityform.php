<?php

namespace Bitrix\Crm\Recurring\Entity\ParameterMapper;

use Bitrix\Crm\Recurring\Calculator;
use Bitrix\Crm\Recurring\Manager;

class EntityForm extends EntityMap
{
	public const FIELD_MODE_NAME = 'MODE';
	public const FIELD_SINGLE_TYPE_NAME = 'SINGLE_TYPE';
	public const FIELD_SINGLE_INTERVAL_NAME = 'SINGLE_INTERVAL_VALUE';
	public const FIELD_MULTIPLE_TYPE_NAME = 'MULTIPLE_TYPE';
	public const FIELD_MULTIPLE_CUSTOM_TYPE_NAME = 'MULTIPLE_CUSTOM_TYPE';
	public const FIELD_MULTIPLE_CUSTOM_INTERVAL_NAME = 'MULTIPLE_CUSTOM_INTERVAL_VALUE';
	public const FIELD_BEGINDATE_TYPE_NAME = 'BEGINDATE_TYPE';
	public const FIELD_BEGINDATE_OFFSET_TYPE_NAME = 'OFFSET_BEGINDATE_TYPE';
	public const FIELD_BEGINDATE_OFFSET_VALUE_NAME = 'OFFSET_BEGINDATE_VALUE';
	public const FIELD_CLOSEDATE_TYPE_NAME = 'CLOSEDATE_TYPE';
	public const FIELD_CLOSEDATE_OFFSET_TYPE_NAME = 'OFFSET_CLOSEDATE_TYPE';
	public const FIELD_CLOSEDATE_OFFSET_VALUE_NAME = 'OFFSET_CLOSEDATE_VALUE';

	protected function getScheme(): array
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

	public static function getInstance(): ?self
	{
		if (self::$instance === null)
		{
			self::$instance = new static();
		}

		return self::$instance;
	}

	public function fillMap(array $params = []): void
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
