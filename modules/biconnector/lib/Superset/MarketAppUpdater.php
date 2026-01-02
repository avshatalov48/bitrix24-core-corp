<?php

namespace Bitrix\BIConnector\Superset;

use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\BIConnector\Integration\Superset\SupersetInitializer;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Rest\Marketplace\Client;

class MarketAppUpdater
{
	private static ?MarketAppUpdater $instance = null;

	public static function getInstance(): self
	{
		return self::$instance ?? new self;
	}

	private function needToCheckUpdates(): bool
	{
		if (SupersetInitializer::getSupersetStatus() !== SupersetInitializer::SUPERSET_STATUS_READY)
		{
			return false;
		}

		$lastChecked = Option::get('biconnector', 'last_time_dashboard_check_update', 0);
		if ($lastChecked <= 0)
		{
			return true;
		}

		$time = DateTime::createFromTimestamp((int)$lastChecked);

		return (new DateTime())->getDiff($time)->d >= 1;
	}

	/**
	 * Gets dashboards app list with available updates.
	 * Returns [ ['CODE' => 'bitrix.bic_deals_complex', 'VERSION' => 3], [...] ].
	 *
	 * @return array Array with app codes and versions to update.
	 */
	private function getDashboardsToUpdate(): array
	{
		$allInstalledApps = SupersetDashboardTable::getList([
			'select' => ['ID', 'APP_ID', 'APP_VERSION' => 'APP.VERSION'],
			'filter' => [
				'=APP.ACTIVE' => 'Y',
				'=APP.INSTALLED' => 'Y',
			],
			'cache' => ['ttl' => 3600],
		])->fetchAll();

		$allInstalledCodes = [];
		foreach ($allInstalledApps as $installedApp)
		{
			// Used format [app_code => installed_version] for Client::getUpdates
			$allInstalledCodes[$installedApp['APP_ID']] = $installedApp['APP_VERSION'];
		}

		$updateCodes = [];
		if ($allInstalledCodes)
		{
			$allUpdates = Client::getUpdates($allInstalledCodes);
			if ($allUpdates)
			{
				foreach ($allUpdates['ITEMS'] as $update)
				{
					$updateCodes[] = [
						'CODE' => $update['CODE'],
						'VERSION' => $update['VER'],
					];
				}
			}
		}

		return $updateCodes;
	}

	/**
	 * Checks for dashboard updates if needed (once a day) and installs necessary updates.
	 * Should be called only from MarketDashboardManager.
	 * @see MarketDashboardManager
	 *
	 * @return Result
	 */
	public function updateApplications(): Result
	{
		$result = new Result();

		if (!$this->needToCheckUpdates())
		{
			return $result;
		}

		$dashboardsToUpdate = $this->getDashboardsToUpdate();

		$manager = MarketDashboardManager::getInstance();
		foreach ($dashboardsToUpdate as $dashboard)
		{
			$installResult = $manager->installApplication($dashboard['CODE'], $dashboard['VERSION']);
			if (!$installResult->isSuccess())
			{
				$result->addErrors($installResult->getErrors());
			}
		}

		Option::set('biconnector', 'last_time_dashboard_check_update', (new DateTime())->getTimestamp());

		return $result;
	}
}
