<?php
namespace Bitrix\Crm\Widget;

use Bitrix\Main;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;

class Filter
{
	const MIN_YEAR = 1000;
	const MAX_YEAR = 9999;

	/** @var int */
	private $periodTypeID = '';
	/** @var int */
	private $year = 0;
	/** @var int */
	private $quarter = 0;
	/** @var int */
	private $month = 0;
	/** @var array[int] */
	private $responsibleIDs = null;
	/** @var array  */
	private $extras = null;
	/** @var array  */
	private $contextData = null;
	/** @var Date|null  */
	private $start = null;
	/** @var Date|null  */
	private $end = null;
	/** @var string */
	private $contextEntityTypeName = '';
	/** @var int */
	private $contextEntityID = 0;

	public function __construct(array $params)
	{
		$periodTypeID = isset($params['periodType']) ? $params['periodType'] : FilterPeriodType::UNDEFINED;

		$this->setPeriodTypeID($periodTypeID);
		$this->setYear(isset($params['year']) ? (int)$params['year'] : 1970);
		$this->setQuarter(isset($params['quarter']) ? (int)$params['quarter'] : 1);
		$this->setMonth(isset($params['month']) ? (int)$params['month'] : 1);

		if($periodTypeID === FilterPeriodType::BEFORE)
		{
			if(isset($params['end']) && $params['end'] instanceof Date)
			{
				$this->setEnd($params['end']);
			}
		}

		$this->setResponsibleIDs(isset($params['responsibleIDs']) && is_array($params['responsibleIDs'])
			? $params['responsibleIDs'] : array());
		$this->setExtras(isset($params['extras']) && is_array($params['extras'])
			? $params['extras'] : array());
	}

	/**
	* @return boolean
	*/
	public function isEmpty()
	{
		return $this->periodTypeID === FilterPeriodType::UNDEFINED;
	}
	/**
	* @return string
	*/
	public function getPeriodTypeID()
	{
		return $this->periodTypeID;
	}
	public function setPeriodTypeID($periodTypeID)
	{
		if($periodTypeID !== FilterPeriodType::UNDEFINED && !FilterPeriodType::isDefined($periodTypeID))
		{
			throw new Main\ArgumentException("Period type '{$periodTypeID}' is unknown in current context.", 'periodTypeID');
		}

		$this->periodTypeID = $periodTypeID;
	}
	/**
	* @return int
	*/
	public function getYear()
	{
		return $this->year;
	}
	/**
	* @return void
	*/
	public function setYear($year)
	{
		if(!is_int($year))
		{
			$year = (int)$year;
		}

		if($year < self::MIN_YEAR || $year > self::MAX_YEAR)
		{
			throw new Main\ArgumentOutOfRangeException('year', self::MIN_YEAR, self::MAX_YEAR);
		}

		$this->year = $year;
	}
	/**
	* @return int
	*/
	public function getQuarter()
	{
		return $this->quarter;
	}
	/**
	* @return void
	*/
	public function setQuarter($quarter)
	{
		if(!is_int($quarter))
		{
			$quarter = (int)$quarter;
		}

		if($quarter < 1 || $quarter > 4)
		{
			throw new Main\ArgumentOutOfRangeException('quarter', 1, 4);
		}

		$this->quarter = $quarter;
	}
	/**
	* @return int
	*/
	public function getMonth()
	{
		return $this->month;
	}
	/**
	* @return void
	*/
	public function setMonth($month)
	{
		if(!is_int($month))
		{
			$month = (int)$month;
		}

		if($month < 1 || $month > 12)
		{
			throw new Main\ArgumentOutOfRangeException('month', 1, 12);
		}

		$this->month = $month;
	}

	public function getStart()
	{
		return $this->start;
	}

	public function setStart(Date $date = null)
	{
		$this->start = $date;
	}

	public function setStartFromPeriod(array $period)
	{
		if(isset($period['START']) && ($period['START'] instanceof Date))
		{
			$this->start = $period['START'];
		}
	}

	public function getEnd()
	{
		return $this->end;
	}

	public function setEnd(Date $date = null)
	{
		$this->end = $date;
	}

	public function setEndFromPeriod(array $period)
	{
		if(isset($period['END']) && ($period['END'] instanceof Date))
		{
			$this->end = $period['END'];
		}
	}

	/**
	* @return array[int]
	*/
	public function getResponsibleIDs()
	{
		return $this->responsibleIDs;
	}
	/**
	* @return void
	*/
	public function setResponsibleIDs(array $responsibleIDs)
	{
		$this->responsibleIDs = $responsibleIDs;
	}
	/**
	* @return array
	*/
	public function getPeriod()
	{
		if($this->isEmpty())
		{
			throw new Main\InvalidOperationException('Could not prepare period. Filter is empty.');
		}

		$result = array();
		if($this->periodTypeID === FilterPeriodType::YEAR)
		{
			$year = $this->year;
			$result['START'] = new Date("{$year}-1-1", 'Y-m-d');
			$result['END'] = new Date("{$year}-12-31", 'Y-m-d');
		}
		elseif($this->periodTypeID === FilterPeriodType::QUARTER)
		{
			$year = $this->year;
			$quarter = $this->quarter;
			$lastMonth = 3 * $quarter;
			$firstMonth = $lastMonth - 2;

			$d = new \DateTime("{$year}-{$lastMonth}-01");
			$lastDay = $d->format('t');

			$result['START'] = new Date("{$year}-{$firstMonth}-01", 'Y-m-d');
			$result['END'] = new Date("{$year}-{$lastMonth}-{$lastDay}", 'Y-m-d');
		}
		elseif($this->periodTypeID === FilterPeriodType::MONTH)
		{
			$year = $this->year;
			$month = $this->month;

			$d = new \DateTime("{$year}-{$month}-01");
			$lastDay = $d->format('t');
			$result['START'] = new Date("{$year}-{$month}-01", 'Y-m-d');
			$result['END'] = new Date("{$year}-{$month}-{$lastDay}", 'Y-m-d');
		}
		elseif($this->periodTypeID === FilterPeriodType::CURRENT_MONTH)
		{
			$d = new \DateTime();
			$year = $d->format('Y');
			$month = $d->format('n');
			$lastDay = $d->format('t');

			$leftBoundary = new \DateTime();
			$leftBoundary->setDate($year, $month, 1);
			$leftBoundary->setTime(0, 0, 0);

			$rightBoundary = new \DateTime();
			$rightBoundary->setDate($year, $month, $lastDay);
			$rightBoundary->setTime(0, 0, 0);

			$result['START'] = Date::createFromPhp($leftBoundary);
			$result['END'] = Date::createFromPhp($rightBoundary);
		}
		elseif($this->periodTypeID === FilterPeriodType::CURRENT_QUARTER)
		{
			$d = new \DateTime();
			$year = $d->format('Y');
			$month = $d->format('n');
			$quarter = $month <= 3 ? 1 : ($month <= 6 ? 2 : ($month <= 9 ? 3 : 4));

			$lastMonth = 3 * $quarter;
			$firstMonth = $lastMonth - 2;

			$d = new \DateTime("{$year}-{$lastMonth}-01");
			$lastDay = $d->format('t');

			$result['START'] = new Date("{$year}-{$firstMonth}-01", 'Y-m-d');
			$result['END'] = new Date("{$year}-{$lastMonth}-{$lastDay}", 'Y-m-d');
		}
		elseif($this->periodTypeID === FilterPeriodType::CURRENT_DAY)
		{
			$d = new \DateTime();
			$year = $d->format('Y');
			$month = $d->format('n');
			$day = $d->format('d');

			$boundary = new \DateTime();
			$boundary->setDate($year, $month, $day);
			$boundary->setTime(0, 0, 0);

			$result['START'] = Date::createFromPhp($boundary);
			$result['END'] = Date::createFromPhp($boundary);
		}
		elseif($this->periodTypeID === FilterPeriodType::LAST_DAYS_90
			|| $this->periodTypeID === FilterPeriodType::LAST_DAYS_60
			|| $this->periodTypeID === FilterPeriodType::LAST_DAYS_30
			|| $this->periodTypeID === FilterPeriodType::LAST_DAYS_7)
		{
			$rightBoundary = new \DateTime();
			$rightBoundary->setTime(0, 0, 0);

			$leftBoundary = new \DateTime();
			$leftBoundary->setTime(0, 0, 0);

			$intervalLength = 7;
			if($this->periodTypeID === FilterPeriodType::LAST_DAYS_90)
			{
				$intervalLength = 90;
			}
			elseif($this->periodTypeID === FilterPeriodType::LAST_DAYS_60)
			{
				$intervalLength = 60;
			}
			elseif($this->periodTypeID === FilterPeriodType::LAST_DAYS_30)
			{
				$intervalLength = 30;
			}

			$intervalLength -= 1;
			$interval = new \DateInterval("P{$intervalLength}D");
			$interval->invert = 1;
			$leftBoundary->add($interval);

			$result['START'] = Date::createFromPhp($leftBoundary);
			$result['END'] = Date::createFromPhp($rightBoundary);
		}
		elseif($this->periodTypeID === FilterPeriodType::BEFORE)
		{
			$result['START'] = null;
			$result['END'] = $this->end !== null ? $this->end : new Date();
		}
		return $result;
	}
	/**
	 * Get extra parameters.
	 * @return array
	*/
	public function getExtras()
	{
		return $this->extras;
	}
	/**
	 * Setup extra parameters.
	 * @param array $extras Parameters.
	 * @return void
	*/
	public function setExtras(array $extras)
	{
		$this->extras = $extras;
	}
	/**
	 * @return string
	 */
	public function getContextEntityTypeName()
	{
		return $this->contextEntityTypeName;
	}

	/**
	 * @param string $entityTypeName
	 */
	public function setContextEntityTypeName($entityTypeName)
	{
		$entityTypeName = (string)$entityTypeName;
		$typeID = \CCrmOwnerType::ResolveID($entityTypeName);
		if ($typeID === \CCrmOwnerType::Undefined)
			throw new Main\ArgumentException("Entity type name '{$entityTypeName}' is unknown in current context.", 'entityTypeName');
		$this->contextEntityTypeName = (string)$entityTypeName;
	}
	/**
	 * @return string
	 */
	public function getContextEntityID()
	{
		return $this->contextEntityID;
	}

	/**
	 * @param int $entityID
	 */
	public function setContextEntityID($entityID)
	{
		$this->contextEntityID = (int)$entityID;
	}
	/**
	 * Get extra parameter value.
	 * @param string $name Parameter name.
	 * @param mixed $defaultValue Default value.
	 * @return mixed
	 */
	public function getExtraParam($name, $defaultValue)
	{
		return isset($this->extras[$name]) ? $this->extras[$name] : $defaultValue;
	}
	/**
	 * Set extra parameter value.
	 * @param string $name Parameter name.
	 * @param mixed $value Parameter value.
	 * @return void
	*/
	public function setExtraParam($name, $value)
	{
		$this->extras[$name] = $value;
	}
	/**
	 * Remove extra parameter.
	 * @param string $name Parameter name.
	 * @return void
	 */
	public function removeExtraParam($name)
	{
		unset($this->extras[$name]);
	}

	/**
	* @return array
	*/
	public function getParams()
	{
		$result = array('periodType' => $this->periodTypeID);

		if($this->periodTypeID === FilterPeriodType::YEAR
			|| $this->periodTypeID === FilterPeriodType::QUARTER
			|| $this->periodTypeID === FilterPeriodType::MONTH)
		{
			$result['year'] = $this->year;
		}

		if($this->periodTypeID === FilterPeriodType::QUARTER)
		{
			$result['quarter'] = $this->quarter;
		}
		elseif($this->periodTypeID === FilterPeriodType::MONTH)
		{
			$result['month'] = $this->month;
		}
		elseif($this->periodTypeID === FilterPeriodType::BEFORE && $this->end !== null)
		{
			$result['end'] = $this->end;
		}

		if(is_array($this->responsibleIDs))
		{
			$result['responsibleIDs'] = $this->responsibleIDs;
		}

		if(is_array($this->extras))
		{
			$result['extras'] = $this->extras;
		}

		return $result;
	}
	/**
	* @return array
	*/
	public static function externalizeParams(array $params)
	{
		$periodTypeID = isset($params['periodType']) ? $params['periodType'] : FilterPeriodType::UNDEFINED;
		$year = isset($params['year']) ? $params['year'] : 0;
		$quarter = isset($params['quarter']) ? $params['quarter'] : 0;
		$month = isset($params['month']) ? $params['month'] : 0;
		$end = isset($params['end']) ? $params['end'] : null;

		$periodParts = array($periodTypeID);
		if($year > 0
			&& ($periodTypeID === FilterPeriodType::YEAR
				|| $periodTypeID === FilterPeriodType::QUARTER
				|| $periodTypeID === FilterPeriodType::MONTH))
		{
			$periodParts[] = $year;
		}
		if($quarter > 0 && $periodTypeID === FilterPeriodType::QUARTER)
		{
			$periodParts[] = $quarter;
		}
		if($month > 0 && $periodTypeID === FilterPeriodType::MONTH)
		{
			$periodParts[] = $month;
		}

		$result = array('PERIOD' => implode('-', $periodParts));

		if($end instanceof Date && $periodTypeID === FilterPeriodType::BEFORE)
		{
			$result['END'] = $end->format('Y-m-d');
		}

		$responsibleIDs = isset($params['responsibleIDs']) ? $params['responsibleIDs'] : null;
		if(is_array($responsibleIDs) && !empty($responsibleIDs))
		{
			$result['RESPONSIBLE_ID'] = $responsibleIDs;
		}

		return $result;
	}
	/**
	* @return array
	*/
	public static function internalizeParams(array $params)
	{
		$result = array('periodType' => FilterPeriodType::UNDEFINED);
		$period = isset($params['PERIOD']) ? $params['PERIOD'] : '';
		if(is_array($period))
		{
			$result['periodType'] = $period['periodType'];
			if($result['periodType'] === FilterPeriodType::YEAR
					|| $result['periodType'] === FilterPeriodType::QUARTER
					|| $result['periodType'] === FilterPeriodType::MONTH)
			{
				$result['year'] = (int)$period['year'];
			}
			if($result['periodType'] === FilterPeriodType::QUARTER)
			{
				$result['quarter'] = (int)$period['quarter'];
			}
			if($result['periodType'] === FilterPeriodType::MONTH)
			{
				$result['month'] = (int)$period['month'];
			}
		}
		else
		{
			$periodParts = explode('-', $period);
			$periodPartCount = count($periodParts);
			if($periodPartCount > 0)
			{
				$result['periodType'] = $periodParts[0];
			}
			if($periodPartCount > 1
				&& ($result['periodType'] === FilterPeriodType::YEAR
					|| $result['periodType'] === FilterPeriodType::QUARTER
					|| $result['periodType'] === FilterPeriodType::MONTH))
			{
				$result['year'] = (int)$periodParts[1];
			}
			if($periodPartCount > 2 && $result['periodType'] === FilterPeriodType::QUARTER)
			{
				$result['quarter'] = (int)$periodParts[2];
			}
			if($periodPartCount > 2 && $result['periodType'] === FilterPeriodType::MONTH)
			{
				$result['month'] = (int)$periodParts[2];
			}

			if($result['periodType'] === FilterPeriodType::BEFORE && isset($params['END']))
			{
				try
				{
					$result['end'] = new Date($params['END'], 'Y-m-d');
				}
				catch(Main\ObjectException $ex)
				{
				}
			}
		}

		if(isset($params['RESPONSIBLE_ID']))
		{
			$responsibleIDs = array();
			if(is_array($params['RESPONSIBLE_ID']))
			{
				foreach($params['RESPONSIBLE_ID'] as $userID)
				{
					if($userID > 0)
					{
						$responsibleIDs[] = (int)$userID;
					}
				}
			}
			elseif($params['RESPONSIBLE_ID'] > 0)
			{
				$responsibleIDs[] = (int)$params['RESPONSIBLE_ID'];
			}

			if(!empty($responsibleIDs))
			{
				$result['responsibleIDs'] = $responsibleIDs;
			}
		}

		return $result;
	}
	/**
	 * Merge params of two filters.
	 * If parameter is absent in target filter it will be copied from seed filter.
	 * @param Filter $seed Seed (is source filter).
	 * @param Filter $target Target (is target filter).
	 * @param array $options Options array.
	 * @return void
	 */
	public static function merge(Filter $seed, Filter $target, array $options = null)
	{
		$overridePeriod = is_array($options) && isset($options['overridePeriod']) ? $options['overridePeriod'] : false;
		if($target->periodTypeID === FilterPeriodType::UNDEFINED || $overridePeriod)
		{
			$target->periodTypeID = $seed->periodTypeID;
			$target->year = $seed->year;
			$target->quarter = $seed->quarter;
			$target->month = $seed->month;

			if($seed->start !== null)
			{
				$target->start = $seed->start;
			}
			if($seed->end !== null)
			{
				$target->end = $seed->end;
			}
		}

		$target->responsibleIDs = array_merge($target->responsibleIDs, $seed->responsibleIDs);
		$target->extras = array_merge($target->extras, $seed->extras);
		if ($target->getContextEntityTypeName() === '' && $seed->getContextEntityTypeName() !== '')
		{
			$target->setContextEntityTypeName($seed->getContextEntityTypeName());
			$target->setContextEntityID($seed->getContextEntityID());
		}
	}
	/**
	 * Sanitize parameter array (before pass in constructor)
	 * @param array $params Parameter array.
	 * @return void
	 */
	public static function sanitizeParams(array &$params)
	{
		if(isset($params['periodType']) && !FilterPeriodType::isDefined($params['periodType']))
		{
			unset($params['periodType']);
		}

		if(isset($params['year']) && ($params['year'] < self::MIN_YEAR || $params['year'] > self::MAX_YEAR))
		{
			unset($params['year']);
		}

		if(isset($params['quarter']) && ($params['quarter'] < 1 || $params['quarter'] > 4))
		{
			unset($params['quarter']);
		}

		if(isset($params['month']) && ($params['month'] < 1 || $params['month'] > 12))
		{
			unset($params['month']);
		}
	}

	/**
	 * Convert System Date Type to Widget Period.
	 * @param array $filter Source Filter.
	 * @param string $fieldName Period Field Name.
	 */
	public static function convertPeriodFromDateType(array &$filter, $fieldName)
	{
		$key = "{$fieldName}_datesel";
		$period = FilterPeriodType::convertFromDateType(isset($filter[$key]) ? $filter[$key] : '');
		if($period === FilterPeriodType::UNDEFINED)
		{
			$period = FilterPeriodType::LAST_DAYS_30;
		}

		$filter[$fieldName] = array('periodType' => $period);
		foreach(array('year', 'quarter', 'month') as $field)
		{
			$key = "{$fieldName}_{$field}";
			if(isset($filter[$key]))
			{
				$filter[$fieldName][$field] = $filter[$key];
			}
		}
	}
	public static function getDateType(array &$filter, $fieldName)
	{
		$key = "{$fieldName}_datesel";
		return isset($filter[$key]) ? $filter[$key] : '';
	}
	public static function addDateType(array &$filter, $fieldName, $dateType)
	{
		$filter["{$fieldName}_datesel"] = $dateType;
	}
}