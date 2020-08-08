<?php
namespace Bitrix\Crm\Recurring\Entity\ParameterMapper;

use \Bitrix\Crm\Recurring\Manager;
use \Bitrix\Crm\Recurring\Calculator;
use \Bitrix\Crm\Recurring\Entity\Deal;
use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class RestDeal extends DealMap
{
	/** @var RestDeal */
	protected static $instance = null;

	const FIELD_MODE_NAME = 'MODE';
	const FIELD_SINGLE_INTERVAL_NAME = 'SINGLE_BEFORE_START_DATE_VALUE';
	const FIELD_SINGLE_TYPE_NAME = 'SINGLE_BEFORE_START_DATE_TYPE';
	const FIELD_MULTIPLE_CUSTOM_TYPE_NAME = 'MULTIPLE_TYPE';
	const FIELD_MULTIPLE_CUSTOM_INTERVAL_NAME = 'MULTIPLE_INTERVAL';
	const FIELD_BEGINDATE_OFFSET_TYPE_NAME = 'OFFSET_BEGINDATE_TYPE';
	const FIELD_BEGINDATE_OFFSET_VALUE_NAME = 'OFFSET_BEGINDATE_VALUE';
	const FIELD_CLOSEDATE_OFFSET_TYPE_NAME = 'OFFSET_CLOSEDATE_TYPE';
	const FIELD_CLOSEDATE_OFFSET_VALUE_NAME = 'OFFSET_CLOSEDATE_VALUE';

	protected function getScheme()
	{
		return [
			self::FIELD_MODE => self::FIELD_MODE_NAME,
			self::FIELD_SINGLE_INTERVAL => self::FIELD_SINGLE_INTERVAL_NAME,
			self::FIELD_SINGLE_TYPE => self::FIELD_SINGLE_TYPE_NAME,
			self::FIELD_MULTIPLE_CUSTOM_TYPE => self::FIELD_MULTIPLE_CUSTOM_TYPE_NAME,
			self::FIELD_MULTIPLE_CUSTOM_INTERVAL => self::FIELD_MULTIPLE_CUSTOM_INTERVAL_NAME,
			self::FIELD_BEGINDATE_OFFSET_TYPE => self::FIELD_BEGINDATE_OFFSET_TYPE_NAME,
			self::FIELD_BEGINDATE_OFFSET_VALUE => self::FIELD_BEGINDATE_OFFSET_VALUE_NAME,
			self::FIELD_CLOSEDATE_OFFSET_TYPE => self::FIELD_CLOSEDATE_OFFSET_TYPE_NAME,
			self::FIELD_CLOSEDATE_OFFSET_VALUE => self::FIELD_CLOSEDATE_OFFSET_VALUE_NAME,
		];
	}

	public function getFieldsInfo()
	{
		$scheme = $this->getScheme();
		$fields = [];
		foreach ($scheme as $code => $item)
		{
			$fields[$item] = [
				'CAPTION' => Loc::getMessage("CRM_REST_DEAL_PARAMETERS_{$item}_FIELD"),
				'TYPE' => 'integer'
			];
			switch ($code)
			{
				case self::FIELD_MODE:
				case self::FIELD_SINGLE_TYPE:
				case self::FIELD_MULTIPLE_CUSTOM_TYPE:
				case self::FIELD_BEGINDATE_OFFSET_TYPE:
				case self::FIELD_CLOSEDATE_OFFSET_TYPE:
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
			self::$instance = new RestDeal();
		}
		return self::$instance;
	}

	public function fillMap(array $params = [])
	{
		$scheme = $this->getScheme();
		$typeParameters = $this->getTypeParameterList();
		foreach ($scheme as $code => $fieldName)
		{
			$item = $params[$fieldName];
			if ($fieldName === self::FIELD_MODE_NAME)
			{
				$this->map[$code] = $this->resolveModeId($item);
			}
			elseif (in_array($code, $typeParameters) && !empty($item))
			{
				$item = mb_strtolower($item);
				$this->map[$code] = (int)Calculator::resolveTypeId($item);
			}
			else
			{
				$item = (int)$item;
				$this->map[$code] = ($item > 0) ? $item : 0;
			}
		}

		if ((int)$params[self::FIELD_MODE_NAME] === Manager::SINGLE_EXECUTION)
		{
			$this->mode = Manager::SINGLE_EXECUTION;

			$this->map[self::FIELD_SINGLE_TYPE] =
			$this->unitType = (int)Calculator::resolveTypeId($params[self::FIELD_SINGLE_TYPE_NAME]);
			$this->interval = (int)$params[self::FIELD_SINGLE_INTERVAL_NAME];
		}
		else
		{
			$this->mode = Manager::MULTIPLY_EXECUTION;
			$this->unitType = (int)Calculator::resolveTypeId($params[self::FIELD_MULTIPLE_CUSTOM_TYPE_NAME]);
			$this->interval = (int)$params[self::FIELD_MULTIPLE_CUSTOM_INTERVAL_NAME];
			if ($this->interval === 1)
			{
				$this->map[self::FIELD_MULTIPLE_TYPE] = $this->unitType;
			}
			else
			{
				$this->map[self::FIELD_MULTIPLE_TYPE] = Calculator::SALE_TYPE_CUSTOM_OFFSET;
			}
		}

		if (!empty($this->map[self::FIELD_BEGINDATE_OFFSET_TYPE]))
		{
			$this->map[self::FIELD_BEGINDATE_TYPE] = Deal::CALCULATED_FIELD_VALUE;
		}

		if (!empty($this->map[self::FIELD_CLOSEDATE_OFFSET_TYPE]))
		{
			$this->map[self::FIELD_CLOSEDATE_TYPE] = Deal::CALCULATED_FIELD_VALUE;
		}
	}

	/**
	 * @param string $value
	 *
	 * @return int
	 */
	private function resolveModeId($value)
	{
		if ($value === Manager::SINGLE_EXECUTION_NAME)
		{
			return Manager::SINGLE_EXECUTION;
		}
		elseif ($value === Manager::MULTIPLY_EXECUTION_NAME)
		{
			return Manager::MULTIPLY_EXECUTION;
		}

		return (int)$value;
	}

	/**
	 * @return array
	 */
	private function getTypeParameterList()
	{
		return [
			self::FIELD_CLOSEDATE_OFFSET_TYPE,
			self::FIELD_BEGINDATE_OFFSET_TYPE,
			self::FIELD_SINGLE_TYPE,
			self::FIELD_MULTIPLE_CUSTOM_TYPE
		];
	}

	public function convert(Map $map)
	{
		parent::convert($map);
		if ((int)$this->mode === Manager::MULTIPLY_EXECUTION)
		{
			if ($this->interval === 1)
			{
				$this->map[self::FIELD_MULTIPLE_CUSTOM_TYPE] = $this->unitType;
				$this->map[self::FIELD_MULTIPLE_CUSTOM_INTERVAL] = $this->interval;
			}
		}
	}

	/**
	 * @return array
	 */
	public function getFormattedMap()
	{
		$result = parent::getFormattedMap();

		$typeParameters = $this->getTypeParameterList();
		foreach ($typeParameters as $typeId)
		{
			$fieldName = $this->getFieldName($typeId);
			$currentValue = $result[$fieldName];
			$result[$fieldName] = Calculator::resolveTypeName($currentValue);
		}

		$result[self::FIELD_MODE_NAME] = $this->resolveModeName($result[self::FIELD_MODE_NAME]);

		return $result;
	}

	private function resolveModeName($value)
	{
		if ($value === Manager::SINGLE_EXECUTION)
		{
			return Manager::SINGLE_EXECUTION_NAME;
		}
		elseif ($value === Manager::MULTIPLY_EXECUTION)
		{
			return Manager::MULTIPLY_EXECUTION_NAME;
		}

		return null;
	}
}
