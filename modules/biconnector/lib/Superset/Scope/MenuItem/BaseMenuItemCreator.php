<?php

namespace Bitrix\BIConnector\Superset\Scope\MenuItem;

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard;
use Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard_Collection;
use Bitrix\BIConnector\Superset\Dashboard\UrlParameter\Service;
use Bitrix\BIConnector\Superset\Scope\ScopeService;
use Bitrix\BIConnector\Superset\Scope\MarketCollectionUrlBuilder;
use Bitrix\Intranet\Settings\Tools\ToolsManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use CUtil;

abstract class BaseMenuItemCreator
{
	abstract public function getMenuItemData(EO_SupersetDashboard_Collection $dashboards, array $params = []): array;

	abstract protected function getScopeCode(): string;

	public function createMenuItem(array $urlParams = []): array
	{
		if (!$this->needShowMenuItem())
		{
			return [];
		}

		$scopeDashboardsCollection = ScopeService::getInstance()->getDashboardListByScope($this->getScopeCode());
		if ($scopeDashboardsCollection->isEmpty())
		{
			return [];
		}

		return $this->getMenuItemData($scopeDashboardsCollection, $urlParams);
	}

	protected function getMenuItemTitle(): string
	{
		return Loc::getMessage('BIC_SCOPE_MENU_ITEM_TITLE');
	}

	protected function getAdditionalItems(): array
	{
		if (AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_ACCESS))
		{
			return [
				[
					'ID' => 'SCOPE_MENU_MARKETPLACE',
					'TEXT' => Loc::getMessage('BIC_SCOPE_MENU_ITEM_MARKETPLACE'),
					'ON_CLICK' => $this->getOpenMarketScript(),
				]
			];
		}

		return [];
	}

	protected function getOpenMarketScript(): string
	{
		\Bitrix\Main\UI\Extension::load('biconnector.apache-superset-market-manager');
		$isMarketExists = \Bitrix\Main\Loader::includeModule('market') ? 'true' : 'false';
		$marketUrl = (string)CUtil::JSEscape(
			(new MarketCollectionUrlBuilder())->setScope($this->getScopeCode())->build()
		);
		$analyticSource = 'scope_menu_' . $this->getScopeCode();

		return "BX.BIConnector.ApacheSupersetMarketManager.openMarket({$isMarketExists}, '{$marketUrl}', '{$analyticSource}')";
	}

	protected function getDetailUrl(
		SupersetDashboard $dashboard,
		array $urlValues = [],
		array $external = []
	): string
	{
		$external = array_merge(
			$external,
			['scope' => $this->getScopeCode()],
		);

		return (new Service($dashboard))->getEmbeddedUrl($urlValues, $external);
	}

	protected function needShowMenuItem(): bool
	{
		if (Loader::includeModule('intranet'))
		{
			return ToolsManager::getInstance()->checkAvailabilityByToolId('crm_bi');
		}

		return true;
	}

	protected function createDashboardOpenEventFromMenu(
		SupersetDashboard $dashboard,
		array $params = [],
	): string
	{
		$url = $this->getDetailUrl(
			$dashboard,
			$params,
			['openFrom' => 'menu'],
		);

		return "window.open(`{$url}`, '_blank');";
	}
}
