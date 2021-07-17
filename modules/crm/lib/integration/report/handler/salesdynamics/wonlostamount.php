<?php

namespace Bitrix\Crm\Integration\Report\Handler\SalesDynamics;

use Bitrix\Crm\PhaseSemantics;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Uri;
use Bitrix\Report\VisualConstructor\IReportMultipleData;
use Bitrix\Crm\Integration\Report\Handler;
use Bitrix\Crm\DealTable;

/**
 * Class Deal
 * @package Bitrix\Crm\Integration\Report\Handler
 */
class WonLostAmount extends Handler\Deal implements IReportMultipleData
{
	const PRIMARY_WON = 'PRIMARY_WON';
	const PRIMARY_LOST = 'PRIMARY_LOST';
	const RETURN_WON = 'RETURN_WON';
	const RETURN_LOST = 'RETURN_LOST';
	const TOTAL_WON = 'TOTAL_WON';
	const TOTAL_LOST = 'TOTAL_LOST';

	const LIST_URL = '/crm/deal/analytics/list/';

	public function prepare()
	{
		$filterParameters = $this->getFilterParameters();;
		$categoryId = $filterParameters['CATEGORY_ID']['value'] ?: 0;
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
		$this->addTimePeriodToQuery($query, $filterParameters['TIME_PERIOD']);
		$this->addPermissionsCheck($query);

		$query->addSelect(Query::expr()->sum('OPPORTUNITY'), 'SUM');
		$query->addSelect('ASSIGNED_BY_ID');
		$query->addSelect('CURRENCY_ID');
		$query->addSelect('STAGE_SEMANTIC_ID');
		$query->addSelect('IS_RETURN_CUSTOMER');

		$query->whereIn('STAGE_SEMANTIC_ID', [PhaseSemantics::SUCCESS, PhaseSemantics::FAILURE]);

		return $query;
	}

	public function prepareEntityListFilter($requestParameters)
	{
		$filterParameters = $this->getFilterParameters();

		$query = DealTable::query();
		$query->addSelect('ID');
		$this->addToQueryFilterCase($query, $filterParameters);
		$this->addTimePeriodToQuery($query, $filterParameters['TIME_PERIOD']);
		//$this->addPermissionsCheck($query);

		foreach ($requestParameters as $parameter => $value)
		{
			switch ($parameter)
			{
				case 'ASSIGNED_BY_ID':
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

	protected function addTimePeriodToQuery(Query $query, $timePeriodValue)
	{
		if ($timePeriodValue['from'] !== "" && $timePeriodValue['to'] !== "")
		{
			$toDateValue = ($timePeriodValue['to'] instanceof DateTime) ? $timePeriodValue['to'] : new DateTime($timePeriodValue['to']);
			$fromDateValue = ($timePeriodValue['from'] instanceof DateTime) ? $timePeriodValue['from'] : new DateTime($timePeriodValue['from']);

			$query->whereBetween("CLOSEDATE", $fromDateValue, $toDateValue);
		}
	}

	protected function isConversionCalculateMode()
	{
		return true;
	}

	public function getMultipleData()
	{
		$calculatedData = $this->getCalculatedData();
		$baseCurrency = \CCrmCurrency::GetAccountCurrencyID();

		$resultByUser = [];

		foreach ($calculatedData as $dataItem)
		{
			$userId = $dataItem['ASSIGNED_BY_ID'];
			if($dataItem['IS_RETURN_CUSTOMER'] != 'Y')
			{
				$key = ($dataItem['STAGE_SEMANTIC_ID'] == PhaseSemantics::SUCCESS) ? static::PRIMARY_WON: static::PRIMARY_LOST;
			}
			else
			{
				$key = ($dataItem['STAGE_SEMANTIC_ID'] == PhaseSemantics::SUCCESS) ? static::RETURN_WON: static::RETURN_LOST;
			}
			$totalKey = ($dataItem['STAGE_SEMANTIC_ID'] == PhaseSemantics::SUCCESS) ? static::TOTAL_WON: static::TOTAL_LOST;

			$amount = ($dataItem['CURRENCY_ID'] == $baseCurrency) ? $dataItem['SUM'] : \CCrmCurrency::ConvertMoney($dataItem['SUM'], $dataItem['CURRENCY_ID'], $baseCurrency);

			if(!isset($resultByUser[$userId]))
			{
				$resultByUser[$userId]['value'] = [
					'USER_ID' => $userId,
					static::PRIMARY_WON => 0,
					static::PRIMARY_LOST => 0,
					static::RETURN_WON => 0,
					static::RETURN_LOST => 0,
					static::TOTAL_WON => 0,
					static::TOTAL_LOST => 0
				];
				$resultByUser[$userId]['targetUrl'] = [
					static::PRIMARY_WON => $this->getTargetUrl(static::LIST_URL, [
						'ASSIGNED_BY_ID' => $userId, 'STAGE_SEMANTIC_ID' => PhaseSemantics::SUCCESS, 'IS_RETURN_CUSTOMER' => 'N'
					]),
					static::PRIMARY_LOST => $this->getTargetUrl(static::LIST_URL, [
						'ASSIGNED_BY_ID' => $userId, 'STAGE_SEMANTIC_ID' => PhaseSemantics::FAILURE, 'IS_RETURN_CUSTOMER' => 'N'
					]),
					static::RETURN_WON => $this->getTargetUrl(static::LIST_URL, [
						'ASSIGNED_BY_ID' => $userId, 'STAGE_SEMANTIC_ID' => PhaseSemantics::SUCCESS, 'IS_RETURN_CUSTOMER' => 'Y'
					]),
					static::RETURN_LOST => $this->getTargetUrl(static::LIST_URL, [
						'ASSIGNED_BY_ID' => $userId, 'STAGE_SEMANTIC_ID' => PhaseSemantics::FAILURE, 'IS_RETURN_CUSTOMER' => 'Y'
					]),
					static::TOTAL_WON => $this->getTargetUrl(static::LIST_URL, [
						'ASSIGNED_BY_ID' => $userId, 'STAGE_SEMANTIC_ID' => PhaseSemantics::SUCCESS
					]),
					static::TOTAL_LOST => $this->getTargetUrl(static::LIST_URL, [
						'ASSIGNED_BY_ID' => $userId, 'STAGE_SEMANTIC_ID' => PhaseSemantics::FAILURE,
					]),
				];
			}

			$resultByUser[$userId]['value'][$key] += $amount;
			$resultByUser[$userId]['value'][$totalKey] += $amount;
		}

		return $resultByUser;
	}

	public function getMultipleDemoData()
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
}
