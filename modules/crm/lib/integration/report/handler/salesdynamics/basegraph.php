<?php

namespace Bitrix\Crm\Integration\Report\Handler\SalesDynamics;

use Bitrix\Crm\DealTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UI\Filter\DateType;
use Bitrix\Main\Web\Uri;
use Bitrix\Report\VisualConstructor\IReportMultipleGroupedData;
use Bitrix\Crm\Integration\Report\Handler;
use Bitrix\Main\Application;

/**
 * Class Deal
 * @package Bitrix\Crm\Integration\Report\Handler
 */
class BaseGraph extends Handler\Deal implements IReportMultipleGroupedData
{
	const GROUP_DAY = 1;
	const GROUP_MONTH = 2;
	const GROUP_WEEK_DAY = 3;

	const DATE_INDEX_FORMAT = "Y-m-d";

	const STEP_DAY = "1d";
	const STEP_MONTH = "1m";

	public function prepare()
	{
		$filterParameters = $this->getFilterParameters();;
		$categoryId = $filterParameters['CATEGORY_ID']['value'] ?? 0;
		$userPermission = \CCrmPerms::GetCurrentUserPermissions();
		if (!\CCrmDeal::CheckReadPermission(0, $userPermission, $categoryId))
		{
			return false;
		}

		$query = DealTable::query();
		$this->prepareQuery($query);

		return $query->exec()->fetchAll();
	}

	public function prepareQuery(Query $query)
	{
		$filterParameters = $this->getFilterParameters();
		$this->addToQueryFilterCase($query, $filterParameters);
		$this->addTimePeriodToQuery($query, $filterParameters['TIME_PERIOD'] ?? null);

		$this->addPermissionsCheck($query);

		$query->addSelect(Query::expr()->sum('OPPORTUNITY'), 'SUM');

		$closedDateFormat = $this->getDateGrouping() === static::GROUP_MONTH ? '%%Y-%%m-01' : '%%Y-%%m-%%d';
		$helper = Application::getConnection()->getSqlHelper();
		$query->addSelect(new ExpressionField('CLOSED', $helper->formatDate($closedDateFormat, '%s'), 'CLOSEDATE'));

		$query->addSelect("CURRENCY_ID");

		return $query;
	}

	public function prepareEntityListFilter($requestParameters)
	{
		$filterParameters = $this->getFilterParameters();

		$query = DealTable::query();
		$query->addSelect('ID');
		$this->addToQueryFilterCase($query, $filterParameters);
		//$this->addPermissionsCheck($query);

		foreach ($requestParameters as $parameter => $value)
		{
			switch ($parameter)
			{
				case 'CLOSEDATE':
					$query->where('CLOSEDATE', new Date($value, static::DATE_INDEX_FORMAT));
					break;
				case 'CLOSEDATE_from':
					$query->where('CLOSEDATE', '>=', new Date($value, static::DATE_INDEX_FORMAT));
					break;
				case 'CLOSEDATE_to':
					$query->where('CLOSEDATE', '<', new Date($value, static::DATE_INDEX_FORMAT));
					break;
				case 'IS_RETURN_CUSTOMER':
				case 'STAGE_SEMANTIC_ID':
					$query->where($parameter, $value);
					break;
			}
		}

		return [
			'__JOINS' => [
				[
					'TYPE' => 'INNER',
					'SQL' => 'INNER JOIN('.$query->getQuery().') REP ON REP.ID = L.ID'
				]
			]
		];
	}

	public function getMultipleGroupedData()
	{
		$items = [];
		$labels = [];
		$baseCurrency = \CCrmCurrency::GetAccountCurrencyID();
		$calculatedData = $this->getCalculatedData();
		$normalizedData = [];
		$totalAmount = 0;

		foreach ($calculatedData as $value)
		{
			$closedDate = new Date($value["CLOSED"], static::DATE_INDEX_FORMAT);
			$dateIndex = $closedDate->format(static::DATE_INDEX_FORMAT);
			if($value["CURRENCY_ID"] == $baseCurrency)
			{
				$amount = $value["SUM"];
			}
			else
			{
				$amount = \CCrmCurrency::ConvertMoney($value["SUM"], $value["CURRENCY_ID"], $baseCurrency);
			}
			$totalAmount += $amount;

			if(isset($normalizedData[$dateIndex]))
			{
				$normalizedData[$dateIndex]["SUM"] += $amount;
			}
			else
			{
				$normalizedData[$dateIndex] = [
					"CLOSED" => $closedDate,
					"SUM" => $amount
				];
			}

		}

/*		$filterParameters = $this->getFilterParameters();
		if(isset($filterParameters['TIME_PERIOD']))
		{
			$minDate = DateTime::createFromUserTime($filterParameters['TIME_PERIOD']['from']);
			$maxDate = DateTime::createFromUserTime($filterParameters['TIME_PERIOD']['to']);
		}*/


		if(count($normalizedData) > 0)
		{
			$this->padNormalizedData($normalizedData);
		}

		foreach ($normalizedData as $date => $value)
		{
			$closedDate = $value["CLOSED"] instanceof Date ? $value["CLOSED"] : new Date($date, static::DATE_INDEX_FORMAT);

			$urlParams = [];
			if($this->getDateGrouping() === static::GROUP_MONTH)
			{
				$urlParams['CLOSEDATE_from'] = $closedDate->format(static::DATE_INDEX_FORMAT);
				$urlParams['CLOSEDATE_to'] = (clone $closedDate)->add('1m')->format(static::DATE_INDEX_FORMAT);
			}
			else
			{
				$urlParams['CLOSEDATE'] = $closedDate->format(static::DATE_INDEX_FORMAT);
			}
			$item = [
				"groupBy" => $date,
				"value" => (float)$value["SUM"],
				"label" => $this->formatDateForLabel($closedDate),
				"balloon" => [
					"title" => $this->formatDateForTitle($closedDate),
				],
				"targetUrl" => $this->getTargetUrl('/crm/deal/analytics/list/', $urlParams)
			];

			$items[] = $item;
			$labels[$date] = $this->formatDateForLabel($closedDate);
		}

		return [
			"items" => $items,
			"config" => [
				"groupsLabelMap" => $labels,
				"reportTitle" => $this->getFormElement("label")->getValue(),
				"reportColor" => $this->getFormElement("color")->getValue(),
				"reportTitleShort" => $this->getFormElement("label")->getValue(),
				"reportTitleMedium" => $this->getFormElement("label")->getValue(),
				"amount" => [
					"value" => \CCrmCurrency::MoneyToString($totalAmount, $baseCurrency),
				],
				"dateFormatForLabel" => $this->getDateFormatForLabel(),
				"dateGrouping" => $this->getDateGrouping()
			]
		];
	}

	public function getMultipleGroupedDemoData()
	{
		return [];
	}

	public function getTargetUrl($baseUri, $params = [])
	{
		$uri = new Uri($baseUri);
		$uri->addParams([
			'from_analytics' => 'Y',
			'report_id' => $this->getReport()->getGId()
		]);

		if (!empty($params))
		{
			$uri->addParams($params);
		}
		return $uri->getUri();
	}

	protected function addTimePeriodToQuery(Query $query, $timePeriodValue)
	{
		if (($timePeriodValue['from'] ?? '') !== '' && ($timePeriodValue['to'] ?? '') !== '')
		{
			$toDateValue = new DateTime($timePeriodValue['to']);
			$fromDateValue = new DateTime($timePeriodValue['from']);

			$query->whereBetween("CLOSEDATE", $fromDateValue, $toDateValue);
		}
	}

	protected function getDateGrouping()
	{
		$filter = $this->getFilterParameters();
		if(!isset($filter['TIME_PERIOD']))
		{
			return static::GROUP_DAY;
		}

		$periodDefinition = $filter['TIME_PERIOD']['datesel'];

		switch ($periodDefinition)
		{
			case DateType::YEAR:
			case DateType::QUARTER:
			case DateType::CURRENT_QUARTER:
				return static::GROUP_MONTH;
			case DateType::LAST_WEEK:
			case DateType::CURRENT_WEEK:
			case DateType::NEXT_WEEK:
				return static::GROUP_WEEK_DAY;
			default:
				return static::GROUP_DAY;
		}
	}

	protected function getDateFormatForLabel()
	{
		switch ($this->getDateGrouping())
		{
			case static::GROUP_DAY:
				return Context::getCurrent()->getCulture()->getDayMonthFormat();
			case static::GROUP_WEEK_DAY:
				return "l";
			case static::GROUP_MONTH:
				return "f";
				break;
			default:
				return Context::getCurrent()->getCulture()->getLongDateFormat();
		}
	}

	protected function formatDateForLabel(Date $date)
	{
		return FormatDate($this->getDateFormatForLabel(), $date);
	}

	protected function formatDateForTitle(Date $date)
	{
		switch ($this->getDateGrouping())
		{
			case static::GROUP_MONTH:
				$format = "f Y";
				break;
			default:
				$format = Context::getCurrent()->getCulture()->getLongDateFormat();
		}

		return FormatDate($format, $date);

	}

	protected function isConversionCalculateMode()
	{
		return true;
	}

	public function padNormalizedData(&$normalizedData)
	{
		reset($normalizedData);
		$firstKey = key($normalizedData);
		/** @var Date $minDate */
		$minDate = $normalizedData[$firstKey]['CLOSED'];
		/** @var Date $maxDate */
		$maxDate = $normalizedData[$firstKey]['CLOSED'];

		foreach ($normalizedData as $key => $value)
		{
			/** @var Date $closedDate */
			$closedDate = $value['CLOSED'];
			if($closedDate->getTimestamp() > $maxDate->getTimestamp())
			{
				$maxDate = clone($closedDate);
			}
			if($closedDate->getTimestamp() < $minDate->getTimestamp())
			{
				$minDate = clone($closedDate);
			}
		}

		$this->fillDateGaps($normalizedData, $minDate, $maxDate);
	}

	public function fillDateGaps(&$normalizedData, Date $dateFrom, Date $dateTo)
	{
		$step = ($this->getDateGrouping() == static::GROUP_MONTH) ? static::STEP_MONTH : static::STEP_DAY;
		foreach (static::getDatesRange($dateFrom, $dateTo, $step) as $date)
		{
			$dateIndex = $date->format('Y-m-d');

			if(!isset($normalizedData[$dateIndex]))
			{
				$normalizedData[$dateIndex] = [
					"CLOSED" => $date,
					"SUM" => 0
				];
			}
		}
	}

	/**
	 * Returns range of dates starting with from date and ending with to date.
	 *
	 * @param Date $from Date to start with.
	 * @param Date $to
	 * @param string $step Interval to add on each iteration, has 2 predefined values: BaseGraph::STEP_DAY andBaseGraph::STEP_MONTH.
	 * @return \Generator<Date>
	 * @throws ArgumentException
	 */
	public static function getDatesRange(Date $from, Date $to, $step = self::STEP_DAY)
	{
		$fromTimestamp = $from->getTimestamp();
		$toTimestamp = $to->getTimestamp();
		if($toTimestamp < $fromTimestamp)
		{
			throw new ArgumentException(Loc::getMessage("CRM_REPORT_SALES_DYNAMICS_ERROR_DATE_FROM_SHOULD_PRECED_DATE_TO"));
		}

		$currentDate = clone($from);

		while ($currentDate->getTimestamp() <= $toTimestamp)
		{
			yield $currentDate;

			$currentDate = clone($currentDate);
			$currentDate->add($step);
		}
	}

	protected function getMonthName($monthNum)
	{
		return Loc::getMessage("MONTH_" . $monthNum);
	}
}
