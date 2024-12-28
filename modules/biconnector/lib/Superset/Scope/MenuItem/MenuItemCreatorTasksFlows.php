<?php

declare(strict_types=1);


namespace Bitrix\BIConnector\Superset\Scope\MenuItem;

use Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard_Collection;
use Bitrix\BIConnector\Superset\Scope\ScopeService;

class MenuItemCreatorTasksFlows extends BaseMenuItemCreator
{
	protected function getScopeCode(): string
	{
		return ScopeService::BIC_SCOPE_TASKS_FLOWS;
	}

	public function getMenuItemData(EO_SupersetDashboard_Collection $dashboards, array $params = []): array
	{
		$menuItems = [];
		foreach ($dashboards as $dashboard)
		{
			$menuItems[] = [
				'ID' => "BIC_TASKS_FLOWS_DASHBOARD_{$dashboard->getId()}",
				'TEXT' => $dashboard->getTitle(),
				'URL' => $this->getDetailUrl(
					$dashboard,
					$params,
					[
						'openFrom' => 'flows',
					]
				),
			];
		}

		return $menuItems;
	}
}