<?php

declare(strict_types=1);

namespace Bitrix\BIConnector\Superset\Scope\MenuItem;

use Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard_Collection;
use Bitrix\BIConnector\Superset\Scope\ScopeService;
use Bitrix\Main\Localization\Loc;

final class MenuItemCreatorTasksEfficiency extends BaseMenuItemCreator
{
	private const ITEM_ID = 'BIC_EFFICIENCY_DASHBOARDS';
	private const MENU_ID = 'menu_bic_efficiency_dashboards';
	private const MAX_COUNTER_SIZE = 100;

	protected function getScopeCode(): string
	{
		return ScopeService::BIC_SCOPE_TASKS_EFFICIENCY;
	}

	public function getMenuItemData(EO_SupersetDashboard_Collection $dashboards, array $params = []): array
	{
		$menuItems = [];
		foreach ($dashboards as $dashboard)
		{
			$menuItems[] = [
				'ID' => "BIC_DASHBOARD_{$dashboard->getId()}",
				'TEXT' => $dashboard->getTitle(),
				'URL' => $this->getDetailUrl(
					$dashboard,
					$params,
					['openFrom' => 'menu']
				),
			];
		}

		return [
			'ID' => self::ITEM_ID,
			'MENU_ID' => self::MENU_ID,
			'TEXT' => $this->getMenuItemTitle(),
			'URL' => '',
			'ITEMS' => $menuItems,
			'MAX_COUNTER_SIZE' => self::MAX_COUNTER_SIZE,
		];
	}

	protected function getMenuItemTitle(): string
	{
		return Loc::getMessage('BIC_SCOPE_MENU_ITEM_TASKS_EFFICIENCY_TITLE');
	}
}
