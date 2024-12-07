<?php

namespace Bitrix\BIConnector\Superset\Scope\MenuItem;

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard;
use Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard_Collection;
use Bitrix\BIConnector\Superset\Dashboard\UrlParameter\Service;
use Bitrix\BIConnector\Superset\Scope\ScopeService;
use Bitrix\Main\Localization\Loc;

abstract class BaseMenuItemCreator
{
	abstract public function getMenuItemData(EO_SupersetDashboard_Collection $dashboards, array $params = []): array;

	abstract protected function getScopeCode(): string;

	public function createMenuItem(array $urlParams = []): array
	{
		if (!AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_ACCESS))
		{
			return [];
		}

		if (!$this->needShowMenuItem())
		{
			return [];
		}

		$dashboards = ScopeService::getInstance()->getDashboardListByScope($this->getScopeCode());
		if ($dashboards->isEmpty())
		{
			return [];
		}

		return $this->getMenuItemData($dashboards, $urlParams);
	}

	protected function getMenuItemTitle(): string
	{
		return Loc::getMessage('BIC_SCOPE_MENU_ITEM_TITLE');
	}

	protected function getDetailUrl(
		SupersetDashboard $dashboard,
		array $urlValues = [],
		array $external = []
	): string
	{
		return (new Service($dashboard))->getEmbeddedUrl($urlValues, $external);
	}

	protected function needShowMenuItem(): bool
	{
		return true;
	}
}
