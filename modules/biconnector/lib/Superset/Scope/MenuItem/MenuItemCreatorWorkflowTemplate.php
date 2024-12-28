<?php

namespace Bitrix\BIConnector\Superset\Scope\MenuItem;

use Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard_Collection;
use Bitrix\BIConnector\Superset\Scope\ScopeService;

final class MenuItemCreatorWorkflowTemplate extends BaseMenuItemCreator
{
	protected function getScopeCode(): string
	{
		return ScopeService::BIC_SCOPE_WORKFLOW_TEMPLATE;
	}

	public function getMenuItemData(EO_SupersetDashboard_Collection $dashboards, array $params = []): array
	{
		$menuItems = [];
		foreach ($dashboards as $dashboard)
		{
			$menuItems[] = [
				'ID' => "BIC_WORKFLOW_DASHBOARD_{$dashboard->getId()}",
				'TEXT' => $dashboard->getTitle(),
				'URL' => $this->getDetailUrl(
					$dashboard,
					$params,
					['openFrom' => 'workflow_template']
				),
			];
		}

		return $menuItems;
	}
}
