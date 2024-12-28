<?php

namespace Bitrix\BIConnector\Superset;

use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
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
		$lastChecked = Option::get('biconnector', 'last_time_dashboard_check_update', 0);
		if ($lastChecked <= 0)
		{
			return true;
		}

		$time = DateTime::createFromTimestamp((int)$lastChecked);

		return (new DateTime())->getDiff($time)->d > 1;
	}

	/**
	 * Gets dashboards app list with available updates.
	 * Returns [ ['CODE' => 'bitrix.bic_deals_ru', 'VERSION' => 3], [...] ].
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
	 * Get list of system dashboards which are not installed yet.
	 *
	 * @return array Array with elements like [CODE => app_code, VERSION => null]. Null version means the last available version.
	 */
	private function getNotInstalledSystemDashboards(): array
	{
		$manager = MarketDashboardManager::getInstance();

		$installedApps = SupersetDashboardTable::getList([
			'select' => ['ID', 'APP_ID'],
			'filter' => [
				'=APP.ACTIVE' => 'Y',
				'=APP.INSTALLED' => 'Y',
			],
			'cache' => ['ttl' => 3600],
		])->fetchAll();
		$installedCodes = array_column($installedApps, 'APP_ID');

		$allSystemApps = $manager->getSystemDashboardApps();
		$codes = array_column($allSystemApps, 'CODE');
		$codesToInstall = array_diff($codes, $installedCodes);
		$result = [];
		foreach ($codesToInstall as $app)
		{
			$result[] = [
				'CODE' => $app,
				'VERSION' => null,
			];
		}

		return $result;
	}

	/**
	 * Adds rows to SupersetDashboardTable with system dashboards info.
	 *
	 * @param array $codes App codes to add.
	 * @return void
	 */
	private function addSystemDashboardRows(array $codes): void
	{
		if (!$codes)
		{
			return;
		}

		$manager = MarketDashboardManager::getInstance();
		$allSystemApps = $manager->getSystemDashboardApps();
		$apps = array_filter($allSystemApps, static fn($item) => in_array($item['CODE'], $codes, true));
		foreach ($apps as $app)
		{
			$dashboard = SupersetDashboardTable::getList([
				'filter' => [
					'=APP_ID' => $app['CODE'],
					'=TYPE' => SupersetDashboardTable::DASHBOARD_TYPE_SYSTEM,
				],
				'limit' => 1,
			])->fetchObject();

			if (!$dashboard)
			{
				SupersetDashboardTable::createObject()
					->setTitle($app['NAME'])
					->setType(SupersetDashboardTable::DASHBOARD_TYPE_SYSTEM)
					->setStatus(SupersetDashboardTable::DASHBOARD_STATUS_LOAD)
					->setAppId($app['CODE'])
					->save()
				;
			}
		}
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
		$dashboardsToInstall = $this->getNotInstalledSystemDashboards();

		Option::set('biconnector', 'last_time_dashboard_check_update', (new DateTime())->getTimestamp());
		$this->addSystemDashboardRows(array_column($dashboardsToInstall, 'CODE'));
		$allAppsToInstall = [...$dashboardsToUpdate, ...$dashboardsToInstall];

		$manager = MarketDashboardManager::getInstance();
		foreach ($allAppsToInstall as $dashboard)
		{
			$installResult = $manager->installApplication($dashboard['CODE'], $dashboard['VERSION']);
			if (!$installResult->isSuccess())
			{
				$result->addErrors($installResult->getErrors());
			}
		}

		return $result;
	}
}
