<?php

namespace Bitrix\Crm\Integration\Report\Handler\SalesDynamics;

use Bitrix\Crm\PhaseSemantics;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Type\DateTime;
use Bitrix\Report\VisualConstructor\IReportMultipleData;
use Bitrix\Crm\Integration\Report\Handler;
use Bitrix\Crm\DealTable;

class Conversion extends Handler\Deal implements IReportMultipleData
{
	const COUNT_PRIMARY_WON = 'COUNT_PRIMARY_WON';
	const COUNT_PRIMARY_LOST = 'COUNT_PRIMARY_LOST';
	const COUNT_PRIMARY_IN_WORK = 'COUNT_PRIMARY_IN_WORK';
	const COUNT_RETURN_WON = 'COUNT_RETURN_WON';
	const COUNT_RETURN_LOST = 'COUNT_RETURN_LOST';
	const COUNT_RETURN_IN_WORK = 'COUNT_RETURN_IN_WORK';

	public function prepare()
	{
		$filterParameters = $this->getFilterParameters();
		$categoryId = $filterParameters['CATEGORY_ID']['value'] ?: 0;
		$userPermission = \CCrmPerms::GetCurrentUserPermissions();
		if (!\CCrmDeal::CheckReadPermission(0, $userPermission, $categoryId))
		{
			return false;
		}

		$query = DealTable::query();
		$this->prepareCountClosedQuery($query, $filterParameters);
		$closedData = $query->exec()->fetchAll();

		$query = DealTable::query();
		$this->prepareCountInWorkQuery($query, $filterParameters);
		$inWorkData = $query->exec()->fetchAll();

		return [
			'closed' => $closedData,
			'in_work' => $inWorkData
		];
	}

	public function prepareCountClosedQuery(Query $query, array $filterParameters)
	{
		$this->addToQueryFilterCase($query, $filterParameters);
		$this->addPermissionsCheck($query);
		$this->addTimePeriodToCountClosedQuery($query, $filterParameters['TIME_PERIOD']);

		$query->addSelect(Query::expr()->count('ID'), 'CNT');
		$query->addSelect('ASSIGNED_BY_ID');
		$query->addSelect('STAGE_SEMANTIC_ID');
		$query->addSelect('IS_RETURN_CUSTOMER');

		$query->whereIn('STAGE_SEMANTIC_ID', [PhaseSemantics::SUCCESS, PhaseSemantics::FAILURE]);

		return $query;
	}

	protected function addTimePeriodToCountClosedQuery(Query $query, $timePeriodValue)
	{
		if ($timePeriodValue['from'] !== "" && $timePeriodValue['to'] !== "")
		{
			$toDateValue = ($timePeriodValue['to'] instanceof DateTime) ? $timePeriodValue['to'] : new DateTime($timePeriodValue['to']);
			$fromDateValue = ($timePeriodValue['from'] instanceof DateTime) ? $timePeriodValue['from'] : new DateTime($timePeriodValue['from']);

			$query->whereBetween("CLOSEDATE", $fromDateValue, $toDateValue);
		}
	}

	protected function prepareCountInWorkQuery(Query $query, $filterParameters)
	{
		$this->addToQueryFilterCase($query, $filterParameters);
		$this->addPermissionsCheck($query);
		$this->addTimePeriodToCountInWorkQuery($query, $filterParameters['TIME_PERIOD']);

		$query->addSelect(Query::expr()->count('ID'), 'CNT');
		$query->addSelect('ASSIGNED_BY_ID');
		$query->addSelect('IS_RETURN_CUSTOMER');

		return $query;
	}

	protected function addTimePeriodToCountInWorkQuery(Query $query, $timePeriodValue)
	{
		if ($timePeriodValue['from'] !== "" && $timePeriodValue['to'] !== "")
		{
			$toDateValue = ($timePeriodValue['to'] instanceof DateTime) ? $timePeriodValue['to'] : new DateTime($timePeriodValue['to']);
			$fromDateValue = ($timePeriodValue['from'] instanceof DateTime) ? $timePeriodValue['from'] : new DateTime($timePeriodValue['from']);

			$query->where('DATE_CREATE', '<=', $toDateValue);
			$query->where(Query::filter()
				->logic('or')
				->whereNull('CLOSEDATE')
				->where('CLOSEDATE', '>=', $fromDateValue)
			);
		}
	}

	protected function isConversionCalculateMode()
	{
		return true;
	}

	public function getMultipleData()
	{
		$calculatedData = $this->getCalculatedData();

		$resultByUser = [];
		$countClosed = $calculatedData['closed'];
		foreach ($countClosed as $dataItem)
		{
			$userId = $dataItem['ASSIGNED_BY_ID'];
			if($dataItem['IS_RETURN_CUSTOMER'] != 'Y')
			{
				$key = ($dataItem['STAGE_SEMANTIC_ID'] == PhaseSemantics::SUCCESS) ? static::COUNT_PRIMARY_WON: static::COUNT_PRIMARY_LOST;
			}
			else
			{
				$key = ($dataItem['STAGE_SEMANTIC_ID'] == PhaseSemantics::SUCCESS) ? static::COUNT_RETURN_WON: static::COUNT_RETURN_LOST;
			}

			$resultByUser[$userId]['value']['USER_ID'] = $userId;
			$resultByUser[$userId]['value'][$key] = $dataItem['CNT'];
		}

		$countInWork = $calculatedData['in_work'];
		foreach ($countInWork as $dataItem)
		{
			$userId = $dataItem['ASSIGNED_BY_ID'];
			$key = ($dataItem['IS_RETURN_CUSTOMER'] != 'Y') ? static::COUNT_PRIMARY_IN_WORK : static::COUNT_RETURN_IN_WORK;

			$resultByUser[$userId]['value']['USER_ID'] = $userId;
			$resultByUser[$userId]['value'][$key] = $dataItem['CNT'];
		}

		return $resultByUser;
	}

	public function getMultipleDemoData()
	{

	}
}
