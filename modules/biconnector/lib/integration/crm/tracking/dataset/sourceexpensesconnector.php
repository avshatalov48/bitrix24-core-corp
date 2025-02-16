<?php

namespace Bitrix\BIConnector\Integration\Crm\Tracking\Dataset;

use Bitrix\BIConnector\DataSourceConnector\Connector\Base;
use Bitrix\BIConnector\Integration\Crm\Tracking\ExpensesAggregator;
use Bitrix\BIConnector\Integration\Crm\Tracking\ExpensesProvider\ProviderFactory;
use Bitrix\Crm\Tracking\Internals\SourceExpensesTable;
use Bitrix\Crm\Tracking;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Type\Date;

class SourceExpensesConnector extends Base
{
	public function query(
		array $parameters,
		int $limit,
		array $dateFormats = []
	): \Generator
	{
		$result = new Result();

		$dataResult = $this->getData($parameters, $dateFormats);
		if (!$dataResult->isSuccess())
		{
			foreach ($dataResult->getErrorMessages() as $errorMessage)
			{
				$result->addError(new Error('QUERY_ERROR', 0, ['description' => $errorMessage]));
			}

			return $result;
		}

		$dto = $dataResult->getConnectorData();
		if (empty($dto->getColumns()))
		{
			$result->addError(new Error('QUERY_ERROR', 0, ['description' => 'No column selected']));

			return $result;
		}

		$endDate = (new Date())->add('+1 day');
		if (!empty($dto->getFilterValue('<=DATE')))
		{
			$endDate = strtotime($dto->getFilterValue('<=DATE'));
			$endDate = Date::createFromTimestamp($endDate);
		}

		$startDateTimestamp = null;
		if (!empty($dto->getFilterValue('>=DATE')))
		{
			$startDateTimestamp = strtotime($dto->getFilterValue('>=DATE'));
		}

		$startDate = clone($endDate);
		$startDate->add('-1 year');
		if ($startDateTimestamp)
		{
			$startDate = Date::createFromTimestamp($startDateTimestamp);
		}

		$aggregator = new ExpensesAggregator(
			...ProviderFactory::getAvailableProviders()
		);
		$expenses = $aggregator->buildDailyExpensesReport($startDate, $endDate);

		$summaryExpenses = [];
		foreach ($expenses as $expense)
		{
			$expense['TIMESTAMP'] = 0;
			if ($expense['DATE'] instanceof Date)
			{
				$expense['TIMESTAMP'] = $expense['DATE']->getTimestamp();
			}

			$summaryExpenses[] = $expense;
		}

		$summaryExpenses = array_merge($summaryExpenses, [...$this->getCustomUserExpenses($startDate, $endDate)]);

		usort($summaryExpenses, static fn($a, $b) => $a['TIMESTAMP'] >= $b['TIMESTAMP']);

		if ($limit > 0 && count($summaryExpenses) > $limit)
		{
			$summaryExpenses = array_slice($summaryExpenses, 0, $limit);
		}

		foreach ($summaryExpenses as $expense)
		{
			$item = [];
			foreach ($dto->getColumns() as $code)
			{
				if (isset($expense[$code]))
				{
					if ($expense[$code] instanceof Date)
					{
						$expense[$code] = $expense[$code]->format('Y-m-d H:i:s');
					}

					$item[$code] = $expense[$code];
				}
				else
				{
					$item[$code] = '';
				}
			}

			yield array_values($item);
		}

		return $result;
	}

	/**
	 * @param Date $startDate
	 * @param Date $endDate
	 *
	 * @return array
	 */
	private function getCustomUserExpenses(Date $startDate, Date $endDate): array
	{
		$resultRows = [];

		$sources = Tracking\Provider::getActualSources();
		$sourceIds = array_column($sources, 'ID');
		$rows = SourceExpensesTable::getList([
			'select' => [
				'CURRENCY' => 'CURRENCY_ID',
				'EXPENSES',
				'DATE' => 'DATE_STAT',
				'ACTIONS',
				'CLICKS' => 'ACTIONS',
				'IMPRESSIONS',
				'SOURCE_ID',
			],
			'filter' => [
				'=SOURCE_ID' => $sourceIds,
				'>=DATE_STAT' => $startDate,
				'<=DATE_STAT' => $endDate,
				'=TYPE_ID' => SourceExpensesTable::TYPE_MANUAL,
			],
		]);

		while ($row = $rows->fetch())
		{
			$row['CAMPAIGN_ID'] = '';
			$row['CAMPAIGN_NAME'] = '';
			$row['TIMESTAMP'] = $row['DATE']->getTimestamp();
			$row['CPC'] =
				$row['ACTIONS'] > 0
					? round($row['EXPENSES'] / $row['ACTIONS'], 2)
					: 0
			;

			$row['CPM'] =
				$row['IMPRESSIONS'] > 0
					? round($row['EXPENSES'] / ($row['IMPRESSIONS'] * 1000), 2)
					: 0
			;

			$resultRows[] = $row;
		}

		return $resultRows;
	}
}

