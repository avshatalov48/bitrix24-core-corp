<?php
namespace Bitrix\Crm\Recurring\Entity\ParameterMapper;

use \Bitrix\Main\Type\Date;

abstract class Map
{
	protected static $instance = null;
	protected $map = [];
	protected $mode = null;
	protected $unitType = null;
	protected $interval = null;

	abstract public function fillMap(array $params = []);
	abstract public function getPreparedMap();
	abstract public static function getInstance();

	protected function getScheme()
	{
		return [];
	}

	protected function getFieldName($code)
	{
		$scheme = $this->getScheme();
		return !empty($scheme[$code]) ? $scheme[$code] : null;
	}

	public function getMap()
	{
		return $this->map;
	}

	public function getFormattedMap()
	{
		$result = [];
		foreach ($this->map as $code => $value)
		{
			$fieldName = $this->getFieldName($code);
			if (!empty($fieldName))
			{
				$result[$fieldName] = $value;
			}
		}
		return $result;
	}

	public function getMode()
	{
		return $this->mode;
	}

	public function getUnitType()
	{
		return $this->unitType;
	}

	public function getInterval()
	{
		return $this->interval;
	}

	public function checkMatchingDate(Date $date)
	{
		return true;
	}

	public function convert(Map $map)
	{
		$this->map = $map->getMap();
		$this->mode = $map->getMode();
		$this->unitType = $map->getUnitType();
		$this->interval = $map->getInterval();
	}
}
