<?php

namespace Bitrix\BIConnector\Integration\Superset\Repository;

use Bitrix\BIConnector\Integration\Superset\Integrator\Integrator;
use Bitrix\BIConnector\Integration\Superset\Model\Dashboard;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard;
use Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard_Collection;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\BIConnector\Integration\Superset\Integrator\Dto;
use Bitrix\BIConnector\Integration\Superset\SupersetInitializer;
use Bitrix\Main\Type\Collection;

final class DashboardRepository
{
	public function __construct(private Integrator $integrator)
	{}

	/**
	 * Load data from DB using additional data from proxy
	 *
	 * @param array $ormParams
	 * @param bool $needLoadProxyData
	 *
	 * @return Dashboard[]
	 */
	public function getList(array $ormParams, bool $needLoadProxyData = false): array
	{
		unset($ormParams['select']);

		$ormParams['cache'] = ['ttl' => 3600];
		$dashboardList = SupersetDashboardTable::getList($ormParams)->fetchCollection();
		$dashboardList->fillUrlParams();
		$dashboardList->fillSource();
		$dashboardExternalIds = $dashboardList->getExternalIdList();
		Collection::normalizeArrayValuesByInt($dashboardExternalIds);

		$dashboards = [];
		$additionalData = $needLoadProxyData
			? $this->loadAdditionalDashboardData($dashboardExternalIds)
			: null
		;

		/** @var SupersetDashboard $dashboard */
		foreach ($dashboardList as $dashboard)
		{
			$externalId = $dashboard->getExternalId();
			if (isset($additionalData[$externalId]) && $additionalData[$externalId] instanceof Dto\Dashboard)
			{
				$dashboardData = $additionalData[$externalId];
				$this->synchronizeDashboard($dashboard, $dashboardData);
			}
			else
			{
				$dashboardData = null;
			}

			$dashboardItem = new Dashboard(
				ormObject: $dashboard,
				dashboardData: $dashboardData
			);

			$editUrl = $dashboardItem->getEditUrl();
			$nativeFilter = $dashboardItem->getNativeFilter();
			if ($nativeFilter)
			{
				$editUrl .= '?native_filters=' . $nativeFilter;
			}

			$dashboardItem->setEditUrl($editUrl);
			$dashboards[] = $dashboardItem;
		}

		return $dashboards;
	}

	public function getById(int $dashboardId, bool $needLoadProxyData = false): ?Dashboard
	{
		$params = [
			'filter' => [
				'=ID' => $dashboardId,
			],
			'limit' => 1,
		];
		$result = $this->getList($params, $needLoadProxyData);
		$dashboard = array_pop($result);
		if (!$dashboard instanceof Dashboard)
		{
			return null;
		}

		return $dashboard;
	}

	public function getCount(array $ormParams): int
	{
		$ormParams['select'] = ['ID'];
		$ormParams['count_total'] = true;

		return SupersetDashboardTable::getList($ormParams)->getCount();
	}

	/**
	 * Load data from proxy
	 *
	 * @param int[] $dashboardExternalIds
	 * @return array|null
	 */
	private function loadAdditionalDashboardData(array $dashboardExternalIds): ?array
	{
		if (!SupersetInitializer::isSupersetReady())
		{
			return null;
		}

		$integratorResult = $this->integrator->getDashboardList($dashboardExternalIds);
		if ($integratorResult->hasErrors())
		{
			return null;
		}
		$dashboardList = $integratorResult->getData();

		if ($dashboardList === null)
		{
			return null;
		}

		$result = [];
		foreach ($dashboardList->dashboards as $item)
		{
			$result[$item->id] = $item;
		}

		return $result;
	}

	private function synchronizeDashboard(SupersetDashboard $dashboard, Dto\Dashboard $proxyData): void
	{
		if ($dashboard->getTitle() !== $proxyData->title)
		{
			$dashboard->setTitle($proxyData->title);
		}

		if (
			$proxyData->dateModify
			&& $dashboard->getDateModify()?->getTimestamp() !== $proxyData->dateModify->getTimestamp()
		)
		{
			$dashboard->setDateModify($proxyData->dateModify);
		}
		elseif (
			$proxyData->dateModify === null
			&& !$dashboard->isDateModifyFilled()
		)
		{
			$dashboard->setDateModify($dashboard->getDateCreate());
		}

		$status =
			$proxyData->published
				? SupersetDashboardTable::DASHBOARD_STATUS_READY
				: SupersetDashboardTable::DASHBOARD_STATUS_DRAFT
		;

		$dashboard->setStatus($status);

		if (
			$dashboard->isTitleChanged()
			|| $dashboard->isDateModifyChanged()
			|| $dashboard->isStatusChanged()
		)
		{
			$dashboard->save();
		}
	}
}
