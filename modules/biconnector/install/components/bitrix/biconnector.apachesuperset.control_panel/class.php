<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\Configuration\Feature;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\BIConnector\Integration\Superset\SupersetInitializer;
use Bitrix\BIConnector\Superset\MarketDashboardManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorableImplementation;
use Bitrix\Main\ErrorCollection;
use Bitrix\BIConnector\Superset\Dashboard\UrlParameter;

class ApacheSupersetControlPanel extends CBitrixComponent implements Errorable
{
	use ErrorableImplementation;

	private bool $isMenuMode = false;

	public function executeComponent()
	{
		$this->errorCollection = new ErrorCollection();
		$this->isMenuMode = ($this->arParams['MENU_MODE'] ?? 'N') === 'Y';

		if (!Loader::includeModule('biconnector'))
		{
			$this->errorCollection->add([new \Bitrix\Main\Error('Module "biconnector" is not installed.')]);
		}

		$this->prepareMenuItems();

		$result = new \Bitrix\Main\Result();
		if ($this->isMenuMode)
		{
			if (!$this->hasErrors())
			{
				$result->setData([
					'MENU_ITEMS' => $this->createFileMenuItems($this->arResult['MENU_ITEMS']),
				]);
			}
			else
			{
				$result->addErrors($this->getErrors());
			}

			return $result;
		}

		if (!$this->hasErrors())
		{
			$this->includeComponentTemplate();
		}
		else
		{
			$this->showErrors();
			$result->addErrors($this->getErrors());
		}

		return $result;
	}

	protected function showErrors(): void
	{
		foreach ($this->getErrors() as $error)
		{
			ShowError($error);
		}
	}

	/**
	 * Translate menu items to flat map for CMenu compatibility
	 *
	 * @param array $items
	 * @param int $depthLevel
	 * @return array
	 */
	protected function createFileMenuItems(array $items, int $depthLevel = 1): array
	{
		$result = [];
		foreach ($items as $item)
		{
			$hasChildren = isset($item['ITEMS']) && is_array($item['ITEMS']) && !empty($item['ITEMS']);

			$result[] = [
				$item['NAME'] ?? $item['TEXT'] ?? '',
				($item['URL'] ?? null),
				[],
				[
					'DEPTH_LEVEL' => $depthLevel,
					'FROM_IBLOCK' => true,
					'IS_PARENT' => $hasChildren,
					'onclick' => $item['ON_CLICK'] ?? null,
					'menu_item_id' => $item['ID'] ?? null,
				]
			];

			if ($hasChildren)
			{
				$result = array_merge($result, $this->createFileMenuItems($item['ITEMS'], $depthLevel + 1));
			}
		}

		return $result;
	}

	private function prepareMenuItems(): void
	{
		if (!$this->canRenderMenuItems())
		{
			$this->arResult['MENU_ITEMS'] = [];

			return;
		}

		\Bitrix\Main\Loader::includeModule('ui');
		\Bitrix\Main\UI\Extension::load([
			'ui.feedback.form',
			'biconnector.apache-superset-feedback-form',
			'biconnector.apache-superset-dashboard-manager',
			'biconnector.apache-superset-analytics',
			'biconnector.apache-superset-market-manager',
		]);

		$isMarketExists = Loader::includeModule('market') ? 'true' : 'false';
		$marketUrl = CUtil::JSEscape(MarketDashboardManager::getMarketCollectionUrl());

		$menuItems = [
			...$this->getDashboardsForTopMenu(),
			[
				'ID' => 'MARKET',
				'TEXT' => Loc::getMessage('BICONNECTOR_CONTROL_PANEL_MENU_ITEM_MARKET_MSGVER_1'),
				'ON_CLICK' => "BX.BIConnector.ApacheSupersetMarketManager.openMarket({$isMarketExists}, '{$marketUrl}', 'menu')",
				'IS_DISABLED' => false,
			],
			[
				'ID' => 'ORDER_DASHBOARD',
				'TEXT' => Loc::getMessage('BICONNECTOR_CONTROL_PANEL_MENU_ITEM_ORDER'),
				'ON_CLICK' => 'BX.Biconnector.ApacheSupersetFeedbackForm.requestIntegrationFormOpen()',
				'IS_DISABLED' => false,
			],
		];

		if (!Feature::isExternalEntitiesEnabled())
		{
			$menuItems[] = [
				'ID' => 'BI_ANALYTICS',
				'TEXT' => Loc::getMessage('BICONNECTOR_CONTROL_PANEL_MENU_ITEM_ANALYTICS'),
				'IS_LOCKED' => true,
				'ON_CLICK' => <<<JS
						top.BX.UI.InfoHelper.show('limit_BI_analyst_workplace');
					JS,
			];
		}
		elseif (
			AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_EXTERNAL_DASHBOARD_CONFIG)
			&& SupersetInitializer::isSupersetExist()
		)
		{
			$menuItems[] = [
				'ID' => 'BI_ANALYTICS',
				'TEXT' => Loc::getMessage('BICONNECTOR_CONTROL_PANEL_MENU_ITEM_ANALYTICS'),
				'ON_CLICK' => 'BX.BIConnector.DashboardManager.openDatasetListSlider()',
				'IS_DISABLED' => false,
			];
		}

		$menuItems[] = [
			'ID' => 'FEEDBACK',
			'TEXT' => Loc::getMessage('BICONNECTOR_CONTROL_PANEL_MENU_ITEM_FEEDBACK'),
			'ON_CLICK' => 'BX.Biconnector.ApacheSupersetFeedbackForm.feedbackFormOpen()',
			'IS_DISABLED' => false,
		];

		$settingsItems = [];
		if (AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_SETTINGS_ACCESS))
		{
			$settingsItems[] = [
				'ID' => 'COMMON_SETTINGS',
				'TEXT' => Loc::getMessage('BICONNECTOR_CONTROL_PANEL_MENU_ITEM_COMMON_SETTINGS'),
				'ON_CLICK' => 'BX.BIConnector.DashboardManager.openSettingsSlider()',
			];
		}

		if (AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_SETTINGS_EDIT_RIGHTS))
		{
			$menuTitle = Loc::getMessage('BICONNECTOR_CONTROL_PANEL_MENU_ITEM_RIGHTS_SETTINGS');
			if (!Feature::isBiBuilderRightsEnabled())
			{
				$settingsItems[] = [
					'ID' => 'RIGHTS_SETTINGS',
					'TEXT' => $menuTitle,
					'IS_LOCKED' => true,
					'ON_CLICK' => <<<JS
						BX.BIConnector.ApacheSupersetAnalytics.sendAnalytics('roles', 'open_editor', {
							c_element: 'menu',
							status: 'low_tariff'
						});
						top.BX.UI.InfoHelper.show('limit_crm_BI_constructor_access_permissions');
					JS,
				];
			}
			else
			{
				$settingsItems[] = [
					'ID' => 'RIGHTS_SETTINGS',
					'TEXT' => $menuTitle,
					'ON_CLICK' => "BX.SidePanel.Instance.open('" . \CUtil::JSEscape('/bi/settings/permissions/') . "', {cacheable: false})",
				];
			}
		}

		if ($settingsItems)
		{
			$menuItems[] = [
				'ID' => 'SETTINGS',
				'TEXT' => Loc::getMessage('BICONNECTOR_CONTROL_PANEL_MENU_ITEM_SETTINGS'),
				'ON_CLICK' => '',
				'ITEMS' => $settingsItems,
			];
		}

		$this->arResult['MENU_ITEMS'] = $menuItems;
	}


	private function canRenderMenuItems(): bool
	{
		$status = \Bitrix\BIConnector\Integration\Superset\SupersetInitializer::getSupersetStatus();

		if ($this->isMenuMode)
		{
			return !in_array($status, [
				\Bitrix\BIConnector\Integration\Superset\SupersetInitializer::SUPERSET_STATUS_DELETED,
				\Bitrix\BIConnector\Integration\Superset\SupersetInitializer::SUPERSET_STATUS_DOESNT_EXISTS,
			]);
		}

		return $status !== \Bitrix\BIConnector\Integration\Superset\SupersetInitializer::SUPERSET_STATUS_DELETED;
	}

	private function getDashboardsForTopMenu(): array
	{
		$userId = \Bitrix\Main\Engine\CurrentUser::get()->getId();
		if (!$userId)
		{
			return [];
		}

		$result = [];
		$topMenuDashboards = CUserOptions::getOption('biconnector', 'top_menu_dashboards', [], $userId);
		$dashboardCollection = SupersetDashboardTable::getList([
			'filter' => [
				'=ID' => $topMenuDashboards,
			],
		])->fetchCollection();

		// When we query dashboards with ids [15, 10], ORM returns them in order [10, 15]. We need to keep initial order.
		$sortedDashboards = [];
		foreach ($topMenuDashboards as $topMenuDashboardId)
		{
			$dashboard = $dashboardCollection->getByPrimary($topMenuDashboardId);
			if ($dashboard)
			{
				$sortedDashboards[] = $dashboard;
			}
		}

		foreach ($sortedDashboards as $dashboard)
		{
			$url = (new UrlParameter\Service($dashboard))->getEmbeddedUrl([], ['openFrom' => 'menu']);
			$result[] = [
				'ID' => "DASHBOARD_{$dashboard->getId()}",
				'TEXT' => $dashboard->getTitle(),
				'ON_CLICK' => "window.open(`{$url}`, '_blank');"
			];
		}

		return $result;
	}
}
