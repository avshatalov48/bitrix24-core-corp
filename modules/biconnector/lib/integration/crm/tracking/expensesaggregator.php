<?php

namespace Bitrix\BIConnector\Integration\Crm\Tracking;

use Bitrix\BIConnector\Integration\Crm\Tracking\ExpensesProvider\Provider;
use Bitrix\Main\Type\Date;
use Bitrix\Main;

final class ExpensesAggregator
{
	private const CACHE_TTL = 60 * 15;

	/**
	 * @var Provider[]
	 */
	private readonly array $providers;

	/**
	 * @param array $providers
	 */
	public function __construct(Provider ...$providers)
	{
		$this->providers = $providers;
	}

	/**
	 * @return array<array{SOURCE_ID: string, EXPENSES: int, DATE: Date}>
	 */
	public function buildDailyExpensesReport(?Date $dateFrom, ?Date $dateTo): array
	{
		$cacheDir = '/biconnector/integration/crm/dailyexpenses/';
		$cacheName = $this->getRequestName($dateFrom, $dateTo);
		$cacheTtl = (int)(Main\Config\Option::get('biconnector', 'crm_daily_expenses_report_cache_ttl', null) ?? self::CACHE_TTL);
		$cache = Main\Data\Cache::createInstance();
		if ($cache->initCache($cacheTtl, $cacheName, $cacheDir))
		{
			return $cache->getVars();
		}

		$expenses = [];
		foreach ($this->providers as $provider)
		{
			$expenses = [...$expenses, ...$provider->getDailyExpenses($dateFrom, $dateTo)];
		}

		$cache->startDataCache();
		$cache->endDataCache($expenses);

		return $expenses;
	}

	private function getRequestName(?Date $dateFrom, ?Date $dateTo): string
	{
		$name = '';
		if ($dateFrom)
		{
			$name .= 'f:' . $dateFrom->getTimestamp() . '|';
		}

		if ($dateTo)
		{
			$name .= 't:' . $dateTo->getTimestamp() . '|';
		}

		$providerIds = [];
		foreach ($this->providers as $provider)
		{
			$providerIds[] = $provider->getId();
		}

		sort($providerIds);

		$name .= 'p:' . implode(',', $providerIds);

		return $name;
	}
}
