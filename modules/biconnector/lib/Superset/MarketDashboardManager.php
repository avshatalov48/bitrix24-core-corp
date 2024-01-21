<?php

namespace Bitrix\BIConnector\Superset;

use Bitrix\BIConnector\Integration\Superset\Integrator\ProxyIntegrator;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\BIConnector\Superset\UI\DashboardManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Rest;
use Bitrix\Rest\AppTable;
use Bitrix\Rest\Marketplace\Application;

final class MarketDashboardManager
{
	private const SYSTEM_DASHBOARDS_TAG = 'bi_system_dashboard';
	public const MARKET_COLLECTION_ID = 'bi_constructor_dashboards';
	private const DASHBOARD_EXPORT_ENABLED_OPTION_NAME = 'bi_constructor_dashboard_export_enabled';

	private static ?MarketDashboardManager $instance = null;
	private ProxyIntegrator $integrator;

	public static function getInstance(): self
	{
		return self::$instance ?? new self;
	}

	private function __construct()
	{
		$this->integrator = ProxyIntegrator::getInstance();
	}

	public static function getMarketCollectionUrl(): string
	{
		return '/market/collection/' . self::MARKET_COLLECTION_ID . '/';
	}

	/**
	 * Sends import query to proxy and add/update rows in b_biconnector_dashboard table.
	 * 1) If it is a clean installing of partner's dashboard - there is no row in dashboard table, method adds it.
	 * Type is MARKET in this case.
	 *
	 * 2) If it is an installing of system dashboards - some of them are already preloaded - there are rows in dashboard
	 * table. Method updates this row - it fills EXTERNAL_ID field. Type is SYSTEM in this case.
	 *
	 * 3) If it is an updating dashboard - row is already filled properly, no more actions required.
	 *
	 * @param string $filePath Path to archive with dashboard to send to superset.
	 * @param Event $event
	 * @return Result
	 */
	public function handleInstallMarketDashboard(string $filePath, Event $event): Result
	{
		$appId = $event->getParameter('APP_ID');
		$appRow = AppTable::getRow([
			'select' => ['ID', 'CODE'],
			'filter' => ['=ID' => $appId],
		]);
		$appCode = $appRow['CODE'];

		$result = new Result();
		$response = $this->integrator->importDashboard($filePath);
		if ($response->hasErrors())
		{
			$row = SupersetDashboardTable::getList([
				'select' => ['ID'],
				'filter' => [
					'=APP_ID' => $appCode,
				],
			])->fetch();

			if ($row !== false)
			{
				$dashboard = SupersetDashboardTable::getByPrimary($row['ID'])->fetchObject();
				$dashboard->setStatus(SupersetDashboardTable::DASHBOARD_STATUS_FAILED);

				DashboardManager::notifyDashboardStatus(
					(int)$row['ID'],
					SupersetDashboardTable::DASHBOARD_STATUS_FAILED
				);

				$dashboard->save();
			}

			$result->addErrors($response->getErrors());

			return $result;
		}

		$type = SupersetDashboardTable::DASHBOARD_TYPE_MARKET;
		if ($this->isSystemAppByAppCode($appCode))
		{
			$type = SupersetDashboardTable::DASHBOARD_TYPE_SYSTEM;
		}

		$dashboards = $response->getData()['dashboards'];
		if (is_array($dashboards))
		{
			$isCleanInstall = true;
			$isUpdateRow = false;
			$row = SupersetDashboardTable::getList([
				'select' => ['ID', 'APP_ID', 'EXTERNAL_ID', 'STATUS'],
				'filter' => ['=APP_ID' => $appCode],
			])->fetch();
			if ($row !== false)
			{
				$isCleanInstall = false;

				if (!isset($row['EXTERNAL_ID']) || $row['STATUS'] === SupersetDashboardTable::DASHBOARD_STATUS_FAILED)
				{
					$isUpdateRow = true;
				}
			}

			foreach ($dashboards as $dashboard)
			{
				if ($isCleanInstall)
				{
					SupersetDashboardTable::add([
						'EXTERNAL_ID' => $dashboard['id'],
						'TITLE' => $dashboard['dashboard_title'],
						'TYPE' => $type,
						'APP_ID' => $appCode,
						'STATUS' => SupersetDashboardTable::DASHBOARD_STATUS_READY,
					]);
				}
				elseif ($isUpdateRow)
				{
					SupersetDashboardTable::update((int)$row['ID'], [
						'EXTERNAL_ID' => $dashboard['id'],
						'TITLE' => $dashboard['dashboard_title'],
						'TYPE' => $type,
						'APP_ID' => $appCode,
						'STATUS' => SupersetDashboardTable::DASHBOARD_STATUS_READY
					]);
				}

				DashboardManager::notifyDashboardStatus((int)$row['ID'], SupersetDashboardTable::DASHBOARD_STATUS_READY);
			}
		}

		return $result;
	}

	/**
	 * Sends import query to proxy to import datasets.
	 * No manipulations in b_biconnector_superset_dashboard table required.
	 *
	 * @param string $filePath Path to archive with datasets to send to superset.
	 * @param Event $event
	 *
	 * @return Result
	 */
	public function handleInstallDatasets(string $filePath, Event $event): Result
	{
		$result = new Result();

		$appId = $event->getParameter('APP_ID');
		$appRow = AppTable::getRow([
			'select' => ['ID', 'CODE'],
			'filter' => ['=ID' => $appId],
		]);
		$appCode = $appRow['CODE'];
		if (!$this->isSystemAppByAppCode($appCode))
		{
			$result->addError(new Error(Loc::getMessage('BI_CONNECTOR_SUPERSET_DELETE_ERROR_DATASET_IMPORT')));

			return $result;
		}

		$response = $this->integrator->importDataset($filePath);
		if ($response->hasErrors())
		{
			$result->addErrors($response->getErrors());
		}

		return $result;
	}

	private function isSystemAppByAppCode(string $appCode): bool
	{
		return preg_match('/^bitrix\.bic_/', $appCode);
	}

	private function isDatasetAppByAppCode(string $appCode): bool
	{
		return preg_match('/^bitrix\.bic_datasets_/', $appCode);
	}

	public function handleDeleteApp(int $appId): Result
	{
		$result = new Result();
		$appRow = AppTable::getRowById($appId);
		if (!isset($appRow['CODE']))
		{
			$result->addError(new Error(Loc::getMessage('BI_CONNECTOR_SUPERSET_DELETE_ERROR_APP_NOT_FOUND')));

			return $result;
		}

		if ($this->isDatasetAppByAppCode($appRow['CODE']))
		{
			$result->addError(new Error(Loc::getMessage('BI_CONNECTOR_SUPERSET_DELETE_ERROR_DATASETS')));

			return $result;
		}

		if ($this->isSystemAppByAppCode($appRow['CODE']))
		{
			$result->addError(new Error(Loc::getMessage('BI_CONNECTOR_SUPERSET_DELETE_ERROR_SYSTEM_DASHBOARD')));

			return $result;
		}

		return $this->handleDeleteMarketDashboard($appRow['CODE']);
	}

	private function handleDeleteMarketDashboard(string $appCode): Result
	{
		$result = new Result();

		$installedDashboardsIterator = SupersetDashboardTable::getList([
			'select' => ['ID', 'EXTERNAL_ID', 'APP_ID', 'TYPE', 'SOURCE_ID', 'APP.ID'],
			'filter' => [
				'=APP_ID' => $appCode,
			],
		]);

		$originalExternalDashboardId = 0;
		$originalDashboardId = 0;
		while ($row = $installedDashboardsIterator->fetch())
		{
			if ($row['TYPE'] === SupersetDashboardTable::DASHBOARD_TYPE_SYSTEM)
			{
				$result->addError(new Error(Loc::getMessage('BI_CONNECTOR_SUPERSET_DELETE_ERROR_SYSTEM_DASHBOARD')));

				return $result;
			}

			if ($row['SOURCE_ID'] !== null)
			{
				$result->addError(new Error(Loc::getMessage('BI_CONNECTOR_SUPERSET_DELETE_ERROR_HAS_COPIES')));

				return $result;
			}

			$originalExternalDashboardId = (int)$row['EXTERNAL_ID'];
			$originalDashboardId = (int)$row['ID'];
		}

		if ($originalExternalDashboardId > 0)
		{
			$response = $this->integrator->deleteDashboard([$originalExternalDashboardId]);
			if ($response->hasErrors())
			{
				$result->addErrors($response->getErrors());

				return $result;
			}

			SupersetDashboardTable::delete($originalDashboardId);
		}

		return $result;
	}

	public function handleUninstallMarketApp(string $appCode): Result
	{
		$result = new Result();
		$uninstallResult = Application::uninstall($appCode, true, 'dashboard');
		if (isset($uninstallResult['error']))
		{
			$result->addError(new Error($uninstallResult['error']));
		}

		return $result;
	}

	public function installInitialDashboards(): Result
	{
		$result = new Result();

		$appList = $this->getSystemApps();
		$systemAppCodes = array_column($appList, 'CODE');
		foreach ($systemAppCodes as $code)
		{
			if ($this->isDatasetAppByAppCode($code))
			{
				$installResult = $this->installApplication($code);
				if (!$installResult->isSuccess())
				{
					$result->addErrors($installResult->getErrors());
				}
			}
		}

		foreach ($systemAppCodes as $code)
		{
			if (
				!$this->isSystemAppByAppCode($code)
				|| $this->isDatasetAppByAppCode($code)
			)
			{
				continue;
			}

			$row = SupersetDashboardTable::getList([
				'select' => ['ID', 'APP_ID', 'EXTERNAL_ID'],
				'filter' => [
					'=APP_ID' => $code,
				],
				'limit' => 1,
			])->fetch();

			if (!isset($row['EXTERNAL_ID']))
			{
				$installResult = $this->installApplication($code);
				$dashboard = SupersetDashboardTable::getByPrimary($row['ID'])->fetchObject();
				if (!$installResult->isSuccess())
				{
					$result->addErrors($installResult->getErrors());
					$status = SupersetDashboardTable::DASHBOARD_STATUS_FAILED;
					$dashboard->setStatus($status);
					$dashboard->save();
					DashboardManager::notifyDashboardStatus((int)$row['ID'], $status);
				}
			}
		}

		return $result;
	}

	public function reinstallDashboard(int $dashboardId): void
	{
		$dashboard = SupersetDashboardTable::getByPrimary($dashboardId)->fetchObject();
		if ($dashboard === null)
		{
			return;
		}

		if ($dashboard->getStatus() === SupersetDashboardTable::DASHBOARD_STATUS_READY)
		{
			return;
		}

		$appId = $dashboard->getAppId();
		if ($appId === null)
		{
			return;
		}

		$dashboard->setStatus(SupersetDashboardTable::DASHBOARD_STATUS_LOAD);
		$dashboard->save();

		$installResult = $this->installApplication($appId);
		if ($installResult->isSuccess())
		{
			$status = SupersetDashboardTable::DASHBOARD_STATUS_READY;
		}
		else
		{
			$status = SupersetDashboardTable::DASHBOARD_STATUS_FAILED;
		}

		$dashboard->setStatus($status);
		$dashboard->save();
		DashboardManager::notifyDashboardStatus($dashboardId, $status);
	}

	private static function getAppIdByDashboardId(int $dashboardId): ?string
	{
		return SupersetDashboardTable::getByPrimary($dashboardId)?->fetchObject()?->getAppId();
	}

	public function getSystemApps(): array
	{
		$managedCache = \Bitrix\Main\Application::getInstance()->getManagedCache();
		$cacheId = 'biconnector_superset_dashboard_list_market';

		if ($managedCache->read(86400, $cacheId))
		{
			return $managedCache->get($cacheId);
		}

		$appList = Rest\Marketplace\Client::getByTag([self::SYSTEM_DASHBOARDS_TAG])['ITEMS'] ?? [];
		$managedCache->set($cacheId, $appList);

		return $appList;
	}

	public function getSystemDashboardApps(): array
	{
		$systemDashboardApps = [];
		foreach ($this->getSystemApps() as $systemApp)
		{
			if (
				!$this->isDatasetAppByAppCode($systemApp['CODE'])
				&& $this->isSystemAppByAppCode($systemApp['CODE'])
			)
			{
				$systemDashboardApps[] = $systemApp;
			}
		}

		return $systemDashboardApps;
	}

	public function installApplication(string $code, ?int $version = null): Result
	{
		return MarketAppInstaller::getInstance()->installApplication($code, $version);
	}

	public function updateApplications()
	{
		return MarketAppUpdater::getInstance()->updateApplications();
	}

	/**
	 * @return bool
	 */
	public function isExportEnabled(): bool
	{
		return Option::get('biconnector', self::DASHBOARD_EXPORT_ENABLED_OPTION_NAME, 'N') === 'Y';
	}
}
