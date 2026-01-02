<?php

namespace Bitrix\BIConnector\Superset;

use Bitrix\BIConnector\Access\Service\DashboardGroupService;
use Bitrix\BIConnector\Configuration\Feature;
use Bitrix\BIConnector\Integration\Superset\Integrator\Integrator;
use Bitrix\BIConnector\Integration\Superset\Integrator\Request\IntegratorResponse;
use Bitrix\BIConnector\Integration\Superset\Model;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardGroupBindingTable;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardGroupScopeTable;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardGroupTable;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\BIConnector\Integration\Superset\SupersetInitializer;
use Bitrix\BIConnector\Superset\Dashboard\EmbeddedFilter;
use Bitrix\BIConnector\Superset\Logger\MarketDashboardLogger;
use Bitrix\BIConnector\Superset\Scope\ScopeService;
use Bitrix\BIConnector\Superset\Scope\MarketCollectionUrlBuilder;
use Bitrix\BIConnector\Superset\UI\DashboardManager;
use Bitrix\BIConnector\Superset\Dashboard\UrlParameter;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Rest;
use Bitrix\Rest\AppTable;
use Bitrix\Rest\Marketplace\Application;

final class MarketDashboardManager
{
	private const DASHBOARD_EXPORT_ENABLED_OPTION_NAME = 'bi_constructor_dashboard_export_enabled';
	private const EVENT_ON_AFTER_DASHBOARD_INSTALL = 'onAfterDashboardInstall';

	private static ?MarketDashboardManager $instance = null;
	private Integrator $integrator;

	public static function getInstance(): self
	{
		return self::$instance ?? new self;
	}

	private function __construct()
	{
		$this->integrator = Integrator::getInstance();
	}

	public static function getMarketCollectionUrl(): string
	{
		return (new MarketCollectionUrlBuilder())->build();
	}

	/**
	 * Sends import query to proxy and add/update rows in b_biconnector_dashboard table.
	 * 1) If it is a clean installing of partner's dashboard - there is no row in dashboard table, method adds it.
	 * Type is MARKET in this case.
	 *
	 * 2) If it is an installing of system dashboards - all of them are already preloaded - there are rows in dashboard
	 * table. Method updates this row - it fills EXTERNAL_ID field. Type is SYSTEM in this case.
	 *
	 * 3) If it is an updating dashboard - uuid of dashboard can be changed due to dependency uuid from app id. In this
	 * case we need to update EXTERNAL_ID of the row and delete dashboard with old uuid.
	 *
	 *
	 * @param string $filePath Path to archive with dashboard to send to superset.
	 * @param Event $event Event with APP_ID parameter.
	 * @return Result
	 */
	public function handleInstallMarketDashboard(string $filePath, Event $event): Result
	{
		$appId = $event->getParameter('APP_ID');
		$app = AppTable::getList([
			'select' => ['ID', 'CODE', 'APP_NAME'],
			'filter' => ['=ID' => $appId],
			'limit' => 1,
		])
			->fetchObject()
		;
		$appCode = $app->getCode();

		$result = new Result();
		if (!$appCode)
		{
			$result->addError(new Error(Loc::getMessage('BI_CONNECTOR_SUPERSET_ERROR_INSTALL_PROXY')));
			MarketDashboardLogger::logErrors($result->getErrors(), [
				'message' => 'Empty app code',
			]);

			return $result;
		}

		if (SupersetInitializer::isSupersetExist())
		{
			$response = $this->integrator->importDashboard($filePath, $appCode);
			if ($response->hasErrors())
			{
				MarketDashboardLogger::logErrors($response->getErrors(),[
					'message' => 'Dashboard installation error',
					'app_code' => $appCode,
				]);

				$this->handleUnsuccessfulInstall($appCode);
				$result->addError(new Error(Loc::getMessage('BI_CONNECTOR_SUPERSET_ERROR_INSTALL_PROXY')));

				return $result;
			}

			$externalDashboards = $response->getData()['dashboards'];
			if (!is_array($externalDashboards))
			{
				MarketDashboardLogger::logInfo('No dashboards in import response', [
					'app_code' => $appCode,
				]);
				$this->handleUnsuccessfulInstall($appCode);
				$result->addError(new Error(Loc::getMessage('BI_CONNECTOR_SUPERSET_ERROR_INSTALL_PROXY')));

				return $result;
			}

			$externalDashboard = current($externalDashboards);
			$externalDashboardId = (int)$externalDashboard['id'];
			$dashboardTitle = $externalDashboard['dashboard_title'];
			$dashboardStatus = SupersetDashboardTable::DASHBOARD_STATUS_READY;
		}
		else
		{
			$externalDashboardId = null;
			$dashboardTitle = $app->getAppName();
			$dashboardStatus = SupersetDashboardTable::DASHBOARD_STATUS_NOT_INSTALLED;
		}

		$type = self::isSystemAppByAppCode($appCode)
			? SupersetDashboardTable::DASHBOARD_TYPE_SYSTEM
			: SupersetDashboardTable::DASHBOARD_TYPE_MARKET
		;

		$dashboard = SupersetDashboardTable::getList([
			'select' => ['ID', 'APP_ID', 'EXTERNAL_ID', 'STATUS'],
			'filter' => ['=APP_ID' => $appCode],
			'limit' => 1,
		])
			->fetchObject()
		;

		if (!$dashboard)
		{
			$dashboard = SupersetDashboardTable::createObject();
		}
		elseif (
			$dashboard->getExternalId() > 0
			&& $dashboard->getExternalId() !== $externalDashboardId
		)
		{
			$this->integrator->deleteDashboard([$dashboard->getExternalId()]);
		}

		$isDashboardExists = $dashboard->getExternalId() > 0;

		$dashboard
			->setExternalId($externalDashboardId)
			->setTitle($dashboardTitle)
			->setType($type)
			->setAppId($appCode)
			->setStatus($dashboardStatus)
			->setDateModify(new DateTime())
			->save()
		;

		$dashboardId = $dashboard->getId();
		$eventData = [
			'dashboardId' => $dashboardId,
			'type' => $type,
		];
		$onInstallEvent = new Event('biconnector', self::EVENT_ON_AFTER_DASHBOARD_INSTALL, $eventData);
		$onInstallEvent->send();

		DashboardManager::notifyDashboardStatus($dashboard->getId(), SupersetDashboardTable::DASHBOARD_STATUS_READY);

		if ($type === SupersetDashboardTable::DASHBOARD_TYPE_SYSTEM)
		{
			$logMessage = $isDashboardExists
				? 'System dashboard was successfully updated'
				: 'System dashboard was successfully installed'
			;
			MarketDashboardLogger::logInfo($logMessage, ['app_code' => $appCode]);
		}

		$result->setData([
			'dashboard' => $dashboard,
			'isExists' => $isDashboardExists,
		]);

		return $result;
	}

	/**
	 * Sets dashboard settings contained in archive. Sets period, scopes, etc.
	 *
	 * @param Model\SupersetDashboard $dashboard
	 * @param array $dashboardSettings
	 *
	 * @return void
	 */
	public function applyDashboardSettings(Model\SupersetDashboard $dashboard, array $dashboardSettings = []): void
	{
		if (!$dashboardSettings && Feature::isCheckPermissionsByGroup())
		{
			$this->saveDashboardGroupByScope($dashboard->getId(), self::getDefaultDashboardGroupScope());

			return;
		}

		if (isset($dashboardSettings['period']))
		{
			$periodSetting = $dashboardSettings['period'];
			if ($periodSetting['FILTER_PERIOD'] === EmbeddedFilter\DateTime::PERIOD_DEFAULT)
			{
				$dashboard->setDateFilterStart(null);
				$dashboard->setDateFilterEnd(null);
				$dashboard->setFilterPeriod(null);
			}
			elseif ($periodSetting['FILTER_PERIOD'] === EmbeddedFilter\DateTime::PERIOD_RANGE)
			{
				try
				{
					$dateStart = new Date($periodSetting['DATE_FILTER_START']);
					$dateEnd = new Date($periodSetting['DATE_FILTER_END']);
					$includeLastFilterDate = $periodSetting['INCLUDE_LAST_FILTER_DATE'] ?? null;
					$dashboard->setDateFilterStart($dateStart);
					$dashboard->setDateFilterEnd($dateEnd);
					$dashboard->setIncludeLastFilterDate($includeLastFilterDate);
					$dashboard->setFilterPeriod(EmbeddedFilter\DateTime::PERIOD_RANGE);
				}
				catch (\Bitrix\Main\ObjectException)
				{}
			}
			else
			{
				$period = EmbeddedFilter\DateTime::getDefaultPeriod();
				$innerPeriod = $periodSetting['FILTER_PERIOD'] ?? '';
				if (is_string($innerPeriod) && EmbeddedFilter\DateTime::isAvailablePeriod($innerPeriod))
				{
					$period = $innerPeriod;
				}
				$dashboard->setFilterPeriod($period);
			}
		}

		$dashboard->save();

		if (is_array($dashboardSettings['scope'] ?? null))
		{
			$scopes = ScopeService::getInstance()->getDashboardScopes($dashboard->getId());
			$scopesToSave = array_unique([...$scopes, ...$dashboardSettings['scope']]);
			ScopeService::getInstance()->saveDashboardScopes($dashboard->getId(), $scopesToSave);

			if (is_array($dashboardSettings['urlParameters'] ?? null) && !empty($dashboardSettings['urlParameters']))
			{
				(new UrlParameter\Service($dashboard))
					->saveDashboardParams(
						$dashboardSettings['urlParameters'],
						$scopesToSave
					)
				;
			}
		}

		if (Feature::isCheckPermissionsByGroup())
		{

			if (
				isset($dashboardSettings['groupCode'])
				&& is_string($dashboardSettings['groupCode'])
				&& in_array($dashboardSettings['groupCode'], ScopeService::getSystemGroupCode())
			)
			{
				$groupScopeCode = $dashboardSettings['groupCode'];
			}
			elseif (isset($scopesToSave))
			{
				$groupScopeCodeList = array_intersect(ScopeService::getSystemGroupCode(), $scopesToSave);

				$additionalScopeMap = [];
				foreach (DashboardGroupService::getAdditionalScopeMap() as $mainScope => $scopeMap)
				{
					foreach ($scopeMap as $scope)
					{
						$additionalScopeMap[$scope] = $mainScope;
					}
				}

				foreach ($scopesToSave as $dashboardScope)
				{
					if (
						isset($additionalScopeMap[$dashboardScope])
						&& !in_array($additionalScopeMap[$dashboardScope], $groupScopeCodeList, true)
					)
					{
						$groupScopeCodeList[] = $additionalScopeMap[$dashboardScope];
					}
				}

				if (empty($groupScopeCodeList))
				{
					$groupScopeCode = self::getDefaultDashboardGroupScope();
				}
			}
			else
			{
				$groupScopeCode = self::getDefaultDashboardGroupScope();
			}

			if (!empty($groupScopeCodeList))
			{
				foreach ($groupScopeCodeList as $scopeCode)
				{
					$this->saveDashboardGroupByScope($dashboard->getId(), $scopeCode);
				}
			}
			else
			{
				$this->saveDashboardGroupByScope($dashboard->getId(), $groupScopeCode);
			}
		}
	}

	/**
	 * Sets status F to dashboard that was installed/updated unsuccessfully.
	 *
	 * @param string $appCode AppCode of dashboard.
	 * @return void
	 */
	private function handleUnsuccessfulInstall(string $appCode): void
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
				SupersetDashboardTable::DASHBOARD_STATUS_FAILED,
			);

			$dashboard->save();
		}
	}

	public static function isSystemAppByAppCode(string $appCode): bool
	{
		$anotherVendors = SystemDashboardManager::getAdditionalSystemVendors();
		$vendorListString = implode('|', array_merge(
			[SystemDashboardManager::SYSTEM_VENDOR_MAIN],
			$anotherVendors,
		));

		return preg_match("/^({$vendorListString})\.bic_/", $appCode);
	}

	public static function isDatasetAppByAppCode(string $appCode): bool
	{
		$anotherVendors = SystemDashboardManager::getAdditionalSystemVendors();
		$vendorListString = implode('|', array_merge(
			[SystemDashboardManager::SYSTEM_VENDOR_MAIN],
			$anotherVendors,
		));

		return preg_match("/^({$vendorListString})\.bic_datasets_/", $appCode);
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

		if (self::isDatasetAppByAppCode($appRow['CODE']))
		{
			return $result;
		}

		if (
			!SystemDashboardManager::canDeleteSystemDashboard()
			&& self::isSystemAppByAppCode($appRow['CODE'])
		)
		{
			$result->addError(new Error(Loc::getMessage('BI_CONNECTOR_SUPERSET_DELETE_ERROR_SYSTEM_DASHBOARD')));

			return $result;
		}

		return $this->handleDeleteMarketDashboard($appRow['CODE']);
	}

	private function handleDeleteMarketDashboard(string $appCode): Result
	{
		$result = new Result();

		$installedDashboards = SupersetDashboardTable::getList([
			'select' => ['ID', 'EXTERNAL_ID', 'APP_ID', 'TYPE', 'SOURCE_ID', 'APP.ID'],
			'filter' => [
				'=APP_ID' => $appCode,
			],
			'order' => ['DATE_CREATE' => 'DESC'],
		])->fetchCollection();

		$originalDashboard = null;
		foreach ($installedDashboards as $dashboard)
		{
			if (
				!SystemDashboardManager::canDeleteSystemDashboard()
				&& $dashboard->getType() === SupersetDashboardTable::DASHBOARD_TYPE_SYSTEM
			)
			{
				$result->addError(new Error(Loc::getMessage('BI_CONNECTOR_SUPERSET_DELETE_ERROR_SYSTEM_DASHBOARD')));

				return $result;
			}

			if ($dashboard->getSourceId() > 0)
			{
				$result->addError(new Error(Loc::getMessage('BI_CONNECTOR_SUPERSET_DELETE_ERROR_HAS_COPIES')));

				return $result;
			}

			$originalDashboard = $dashboard;
		}

		if ($originalDashboard)
		{
			if (SupersetInitializer::isSupersetExist())
			{
				$response = $this->integrator->deleteDashboard([$originalDashboard->getExternalId()]);
				if ($response->hasErrors())
				{
					if ($response->getStatus() === IntegratorResponse::STATUS_NOT_FOUND)
					{
						if ($originalDashboard->getType() === SupersetDashboardTable::DASHBOARD_TYPE_SYSTEM)
						{
							SystemDashboardManager::saveDeletedSystemDashboard($appCode);
						}
						$originalDashboard->delete();

						return $result;
					}

					$result->addError(new Error(Loc::getMessage('BI_CONNECTOR_SUPERSET_ERROR_DELETE_PROXY')));

					return $result;
				}
			}

			if ($originalDashboard->getType() === SupersetDashboardTable::DASHBOARD_TYPE_SYSTEM)
			{
				SystemDashboardManager::saveDeletedSystemDashboard($appCode);
			}
			$originalDashboard->delete();
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

	public function reinstallDashboard(int $dashboardId): void
	{
		$dashboard = SupersetDashboardTable::getByPrimary($dashboardId)->fetchObject();
		if ($dashboard === null)
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

	public static function getMarketItems(array $tags): array
	{
		$managedCache = \Bitrix\Main\Application::getInstance()->getManagedCache();
		$cacheId = 'biconnector_superset_dashboard_list_market_' . implode('_', $tags);

		if ($managedCache->read(86400, $cacheId))
		{
			return $managedCache->get($cacheId);
		}

		$result = [];
		$page = 1;
		$pageSize = 50;
		do
		{
			$appList = Rest\Marketplace\Client::getByTag($tags, $page, $pageSize)['ITEMS'] ?? [];
			if (!$appList)
			{
				return $result;
			}

			foreach ($appList as $item)
			{
				$result[] = $item;
			}
			$page++;
			$needLoadNextPage = count($appList) === $pageSize;
		}
		while ($needLoadNextPage);

		$managedCache->set($cacheId, $result);

		return $result;
	}

	/**
	 * @deprecated
	 * @use SystemDashboardManager::getSystemApps()
	 * @return array
	 */
	public function getSystemDashboardApps(): array
	{
		return SystemDashboardManager::getSystemApps();
	}

	/**
	 * @deprecated
	 * @use SystemDashboardManager::getSystemApps()
	 * @return array
	 */
	public function getSystemApps(): array
	{
		return SystemDashboardManager::getSystemApps();
	}

	public function installApplication(string $code, ?int $version = null): Result
	{
		return MarketAppInstaller::getInstance()->installApplication($code, $version);
	}

	public function updateApplications(): Result
	{
		return MarketAppUpdater::getInstance()->updateApplications();
	}

	/**
	 * @return bool
	 */
	public function isExportEnabled(): bool
	{
		if (Option::get('biconnector', self::DASHBOARD_EXPORT_ENABLED_OPTION_NAME, 'N') === 'Y')
		{
			return true;
		}

		return Feature::isBiBuilderExportEnabled();
	}

	/**
	 * @deprecated Will be removed in future updates.
	 */
	public function areInitialDashboardsInstalling(): bool
	{
		return false;
	}

	private function saveDashboardGroupByScope(int $dashboardId, string $groupScopeCode): void
	{
		$group = SupersetDashboardGroupScopeTable::getList([
			'select' => ['GROUP_ID'],
			'filter' => [
				'SCOPE_CODE' => $groupScopeCode,
				'GROUP.TYPE' => SupersetDashboardGroupTable::GROUP_TYPE_SYSTEM,
			],
			'limit' => 1,
		])
			->fetch()
		;
		$groupId = $group['GROUP_ID'] ?? null;

		if ($groupId !== null)
		{
			$existGroupRelation = SupersetDashboardGroupBindingTable::getList([
				'select' => ['GROUP'],
				'filter' => [
					'GROUP.ID' => $groupId,
					'DASHBOARD_ID' => $dashboardId,
				],
				'limit' => 1,
			])
				->fetchObject()
			;

			if (is_null($existGroupRelation))
			{
				$groupRelation = SupersetDashboardGroupBindingTable::createObject();
				$groupRelation->setGroupId((int)$groupId);
				$groupRelation->setDashboardId($dashboardId);
				$groupRelation->save();
			}

			$scopes = ScopeService::getInstance()->getDashboardScopes($dashboardId);
			if (!in_array($groupScopeCode, $scopes, true))
			{
				$scopes[] = $groupScopeCode;
				ScopeService::getInstance()->saveDashboardScopes($dashboardId, $scopes);
			}
		}
	}

	public static function getDefaultDashboardGroupScope(): string
	{
		return ScopeService::BIC_SCOPE_CRM;
	}
}
