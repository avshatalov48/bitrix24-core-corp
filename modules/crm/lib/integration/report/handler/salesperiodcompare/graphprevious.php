<?php

namespace Bitrix\Crm\Integration\Report\Handler\SalesPeriodCompare;

use Bitrix\Crm\Integration\Report\Handler\SalesDynamics\BaseGraph;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Application;

class GraphPrevious extends BaseGraph
{
	public function prepareQuery(Query $query)
	{
		$filterParameters = $this->getFilterParameters();
		$this->addToQueryFilterCase($query, $filterParameters);
		$this->addTimePeriodToQuery($query, $filterParameters['PREVIOUS_PERIOD']);;

		$this->addPermissionsCheck($query);

		$query->addSelect(Query::expr()->sum('OPPORTUNITY'), 'SUM');

		$closedDateFormat = $this->getDateGrouping() === static::GROUP_MONTH ? '%%Y-%%m-01' : '%%Y-%%m-%%d';
		$helper = Application::getConnection()->getSqlHelper();
		$query->addSelect(new ExpressionField("CLOSED", $helper->formatDate($closedDateFormat, '%s'), "CLOSEDATE"));
		$query->addSelect("CURRENCY_ID");

		$query->where("STAGE_SEMANTIC_ID", PhaseSemantics::SUCCESS);

		return $query;
	}

	public function getTargetUrl($baseUri, $params = [])
	{
		$params["STAGE_SEMANTIC_ID"] = PhaseSemantics::SUCCESS;
		return parent::getTargetUrl($baseUri, $params);
	}

	public function mutateFilterParameter($filterParameters, array $fieldList)
	{
		$fieldList['PREVIOUS_PERIOD'] = [
			'id' => 'PREVIOUS_PERIOD',
			'type' => 'date'
		];

		return parent::mutateFilterParameter($filterParameters, $fieldList);
	}

	public function padNormalizedData(&$normalizedData)
	{
		$filterParameters = $this->getFilterParameters();
		if(!isset($filterParameters['PREVIOUS_PERIOD']))
		{
			throw new SystemException(Loc::getMessage("CRM_REPORT_PERIOD_COMPARE_ERROR_PREV_PERIOD_UNSET"));
		}

		$minDate = DateTime::createFromUserTime($filterParameters['PREVIOUS_PERIOD']['from']);

		reset($normalizedData);
		$firstKey = key($normalizedData);
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
		}

		$this->fillDateGaps($normalizedData, $minDate, $maxDate);

		uasort($normalizedData, function($a, $b)
		{
			return $a['CLOSED']->getTimestamp() <=> $b['CLOSED']->getTimestamp();
		});
	}
}