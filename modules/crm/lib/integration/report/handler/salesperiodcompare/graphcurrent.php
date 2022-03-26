<?php

namespace Bitrix\Crm\Integration\Report\Handler\SalesPeriodCompare;

use Bitrix\Crm\Integration\Report\Handler\SalesDynamics\BaseGraph;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;

class GraphCurrent extends BaseGraph
{
	public function prepareQuery(Query $query)
	{
		parent::prepareQuery($query);
		$query->where("STAGE_SEMANTIC_ID", PhaseSemantics::SUCCESS);
		return $query;
	}

	public function getTargetUrl($baseUri, $params = [])
	{
		$params["STAGE_SEMANTIC_ID"] = PhaseSemantics::SUCCESS;
		return parent::getTargetUrl($baseUri, $params);
	}

	public function padNormalizedData(&$normalizedData)
	{
		$filterParameters = $this->getFilterParameters();
		if(!isset($filterParameters['TIME_PERIOD']))
		{
			throw new SystemException(Loc::getMessage("CRM_REPORT_PERIOD_COMPARE_ERROR_TIME_PERIOD_UNSET"));
		}

		$minDate = new Date($filterParameters['TIME_PERIOD']['from']);

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