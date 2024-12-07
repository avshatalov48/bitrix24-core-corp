<?php

namespace Bitrix\BIConnector\Superset\Scope\MenuItem;

use Bitrix\Main;
use Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard_Collection;
use Bitrix\BIConnector\Superset\Scope\ScopeService;

final class MenuItemCreatorShop extends BaseMenuItemCreator
{
	protected function getScopeCode(): string
	{
		return ScopeService::BIC_SCOPE_SHOP;
	}

	public function getMenuItemData(EO_SupersetDashboard_Collection $dashboards, array $params = []): array
	{
		$menuItems = [];
		foreach ($dashboards as $dashboard)
		{
			$menuItems[] = [
				'items_id' => "BIC_DASHBOARD_{$dashboard->getId()}",
				'parent_menu' => 'menu_bic_dashboards',
				'text' => $dashboard->getTitle(),
				'title' => $dashboard->getTitle(),
				'url' => $this->getDetailUrl(
					$dashboard,
					$params,
					['openFrom' => 'menu']
				),
				'url_constant' => true,
			];
		}

		return [
			'parent_menu' => 'global_menu_store',
			'items_id' => 'menu_bic_dashboards',
			'text' => $this->getMenuItemTitle(),
			'title' => $this->getMenuItemTitle(),
			'sort' => 380,
			'url' => '',
			'url_constant' => true,
			'items' => $menuItems,
		];
	}

	/**
	 * Adds menu item in sites directory
	 *
	 * @see $eventManager->registerEventHandler('main', 'OnBuildGlobalMenu', 'biconnector', '\Bitrix\BIConnector\Superset\Scope\MenuItem\MenuItemCreatorShop', 'buildCrmMenu');
	 *
	 * @param $aGlobalMenu
	 * @param $aModuleMenu
	 * @return void
	 */
	public static function buildCrmMenu(&$aGlobalMenu, &$aModuleMenu): void
	{
		if (Main\Application::getInstance()->getContext()->getRequest()->isAdminSection())
		{
			return;
		}

		/** @see MenuItemCreatorShop::getMenuItemData */
		$menuItem = ScopeService::getInstance()->prepareScopeMenuItem(ScopeService::BIC_SCOPE_SHOP);
		if ($menuItem)
		{
			$aModuleMenu[] = $menuItem;
		}
	}
}