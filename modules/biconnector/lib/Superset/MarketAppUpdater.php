<?php

namespace Bitrix\BIConnector\Superset;

use Bitrix\BIConnector\Superset\Logger\MarketDashboardLogger;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Rest\AppTable;

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

	private function checkForDashboardUpdates(): Result
	{
		$result = new Result();
		$dashboardsToUpdate = [];

		$manager = MarketDashboardManager::getInstance();

		$systemApps = $manager->getSystemApps();
		if (empty($systemApps))
		{
			$result->addError(new Error('MarketAppUpdater: list of system apps is empty.'));

			return $result;
		}

		$systemAppCodes = array_column($systemApps, 'CODE');
		$installedApps = AppTable::getList([
			'select' => ['ID', 'CODE', 'VERSION'],
			'filter' => [
				'@CODE' => $systemAppCodes,
				'=ACTIVE' => 'Y',
				'=INSTALLED' => 'Y',
			],
		])->fetchAll();

		foreach ($installedApps as $installedApp)
		{
			$currentVersion = (int)$installedApp['VERSION'];
			$systemApp = array_filter($systemApps, static fn($app) => $app['CODE'] === $installedApp['CODE']);
			$systemApp = array_pop($systemApp);
			$availableVersion = 0;
			if ($systemApp)
			{
				$availableVersion = (int)$systemApp['VER'];
			}

			if ($availableVersion > $currentVersion)
			{
				$dashboardsToUpdate[] = [
					'CODE' => $installedApp['CODE'],
					'VERSION' => $availableVersion,
				];
			}
		}

		if (count($installedApps) < count($systemAppCodes))
		{
			foreach ($systemAppCodes as $systemAppCode)
			{
				if (!in_array($systemAppCode, array_column($installedApps, 'CODE'), true))
				{
					$dashboardsToUpdate[] = [
						'CODE' => $systemAppCode,
						'VERSION' => null,
					];
				}
			}
		}

		$result->setData(['dashboardsToUpdate' => $dashboardsToUpdate]);
		Option::set('biconnector', 'last_time_dashboard_check_update', (new DateTime())->getTimestamp());

		return $result;
	}

	/**
	 * Checks for dashboard updates if needed (once a day) and installs necessary updates.
	 * Should be called only from MarketDashboardManager.
	 * @see \Bitrix\BIConnector\Superset\MarketDashboardManager
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

		$checkResult = $this->checkForDashboardUpdates();
		if (!$checkResult->isSuccess())
		{
			$result->addErrors($checkResult->getErrors());

			return $result;
		}

		$manager = MarketDashboardManager::getInstance();
		$dashboardsToUpdate = $checkResult->getData()['dashboardsToUpdate'];
		foreach ($dashboardsToUpdate as $dashboard)
		{
			$installResult = $manager->installApplication($dashboard['CODE'], $dashboard['VERSION']);
			if (!$installResult->isSuccess())
			{
				$result->addErrors($installResult->getErrors());
			}
		}

		if (!$result->isSuccess())
		{
			MarketDashboardLogger::logErrors($result->getErrors());
		}

		return $result;
	}
}
