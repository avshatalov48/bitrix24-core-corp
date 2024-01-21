<?php

namespace Bitrix\BIConnector\Integration\Superset\Repository;

use Bitrix\BIConnector\Integration\Superset\Integrator\SupersetIntegrator;
use Bitrix\BIConnector\Integration\Superset\Model\Dashboard;
use Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard;
use Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard_Collection;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\BIConnector\Integration\Superset\Integrator\Dto;
use Bitrix\BIConnector\Integration\Superset\SupersetInitializer;
use Bitrix\Main\Type\Collection;

final class DashboardRepository
{
	public function __construct(private SupersetIntegrator $integrator)
	{}

	/**
	 * Load data from DB using additional data from proxy
	 * @param array $ormParams
	 * @return null|Dashboard[]
	 */
	public function getList(array $ormParams): null|array
	{
		$ormParams['select'] = [
			'*', 'SOURCE'
		];
		$dashboardList = SupersetDashboardTable::getList($ormParams)->fetchCollection();
		$dashboardExternalIds = $dashboardList->getExternalIdList();
		Collection::normalizeArrayValuesByInt($dashboardExternalIds);

		$dashboards = [];
		$additionalData = $this->loadAdditionalDashboardData($dashboardExternalIds);

		/** @var EO_SupersetDashboard $dashboard */
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
			$dashboardItem->setEditUrl($dashboardItem->getEditUrl() . '?native_filters=' . $dashboardItem->getNativeFilter());
			$dashboards[] = $dashboardItem;
		}

		return $dashboards;
	}

	public function getById(int $dashboardId): ?Dashboard
	{
		$params = [
			'filter' => [
				'=ID' => $dashboardId,
			],
			'limit' => 1,
		];
		$result = $this->getList($params);
		$dashboard = array_pop($result);
		if (!$dashboard instanceof Dashboard)
		{
			return null;
		}
		$credentialsResponse =
			$dashboard->getExternalId()
				? $this->integrator->getDashboardEmbeddedCredentials($dashboard->getExternalId())
				: null
		;

		$credentials = $credentialsResponse?->getData();
		if ($credentials !== null)
		{
			$dashboard->setDashboardCredentials($credentials);
		}

		return $dashboard;
	}

	/**
	 * Load data from proxy
	 *
	 * @param int[] $dashboardExternalIds
	 * @return array|null
	 */
	private function loadAdditionalDashboardData(array $dashboardExternalIds): ?array
	{
		if (!SupersetInitializer::isSupersetActive())
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

	private function synchronizeDashboards(EO_SupersetDashboard_Collection $dashboardList, ?array $proxyData): void
	{
		if (empty($proxyData))
		{
			return;
		}

		/** @var EO_SupersetDashboard $dashboard */
		foreach ($dashboardList as $dashboard)
		{
			$dashboardExternalId = (int)$dashboard->getExternalId();
			if (
				isset($proxyData[$dashboardExternalId])
				&& $proxyData[$dashboardExternalId] instanceof Dto\Dashboard
			)
			{
				$title = $proxyData[$dashboardExternalId]->title;
				if ($dashboard->getTitle() !== $title)
				{
					$dashboard->setTitle($title);
					$dashboard->save();
				}
			}
		}
	}

	private function synchronizeDashboard(EO_SupersetDashboard $dashboard, Dto\Dashboard $proxyData): void
	{
		$title = $proxyData->title;
		if ($dashboard->getTitle() !== $title)
		{
			$dashboard->setTitle($title);
			$dashboard->save();
		}
	}
}
