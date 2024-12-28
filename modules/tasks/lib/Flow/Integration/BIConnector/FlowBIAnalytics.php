<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\BIConnector;

use Bitrix\BIConnector\Superset\Dashboard\UrlParameter\Parameter;
use Bitrix\BIConnector\Superset\Scope\MenuItem\MenuItemCreatorTasksFlows;
use Bitrix\BIConnector\Superset\Scope\MenuItem\MenuItemCreatorTasksFlowsFlow;
use Bitrix\BIConnector\Superset\Scope\ScopeService;
use Bitrix\Main\Loader;

final class FlowBIAnalytics
{
	private static ?self $instance = null;

	public static function getInstance(): self
	{
		if (self::$instance === null)
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function getFlowsDashboards(): array
	{
		if (!Loader::includeModule('biconnector'))
		{
			return [];
		}

		if (!class_exists(MenuItemCreatorTasksFlows::class))
		{
			return [];
		}

		$dashboards = ScopeService::getInstance()->prepareScopeMenuItem(
			ScopeService::BIC_SCOPE_TASKS_FLOWS,
		);

		return $this->getPreparedDashboards($dashboards);
	}

	public function getFlowDashboards(int $flowId): array
	{
		if ($flowId <= 0)
		{
			return [];
		}

		if (!Loader::includeModule('biconnector'))
		{
			return [];
		}

		if (!class_exists(MenuItemCreatorTasksFlowsFlow::class))
		{
			return [];
		}

		$dashboards = ScopeService::getInstance()->prepareScopeMenuItem(
			ScopeService::BIC_SCOPE_TASKS_FLOWS_FLOW,
			[
				Parameter::TasksFlowsFlowId->value => $flowId,
			]
		);

		return $this->getPreparedDashboards($dashboards);
	}

	private function getPreparedDashboards(array $dashboards): array
	{
		$items = [];
		foreach ($dashboards as $dashboard)
		{
			$items[$dashboard['ID']] = [
				'id' => $dashboard['ID'],
				'title' => $dashboard['TEXT'],
				'url' => $dashboard['URL'],
			];
		}

		return $items;
	}
}