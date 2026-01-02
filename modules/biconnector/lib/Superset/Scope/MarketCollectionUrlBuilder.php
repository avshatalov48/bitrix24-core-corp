<?php

namespace Bitrix\BIConnector\Superset\Scope;

use Bitrix\Main\Loader;

class MarketCollectionUrlBuilder
{
	private ?string $scope = null;

	/**
	 * @param string $scope value from ScopeService::BIC_SCOPE_* constants
	 *
	 * @return $this
	 */
	public function setScope(string $scope): self
	{
		$this->scope = $scope;

		return $this;
	}

	/**
	 * Builds the URL for the marketplace collection based on the scope.
	 *
	 * @return string
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function build(): string
	{
		$marketPrefix = '/marketplace/';
		if (Loader::includeModule('intranet'))
		{
			$marketPrefix = \Bitrix\Intranet\Binding\Marketplace::getMainDirectory();
		}

		return $marketPrefix . 'collection/' . $this->getMarketCollectionCode() . '/';
	}

	private function getMarketCollectionCode(): string
	{
		return match ($this->scope)
		{
			ScopeService::BIC_SCOPE_CRM => 'bi_constructor_dashboards_crm',
			ScopeService::BIC_SCOPE_TASKS, ScopeService::BIC_SCOPE_TASKS_FLOWS_FLOW => 'bi_constructor_dashboards_tasks',
			ScopeService::BIC_SCOPE_BIZPROC, ScopeService::BIC_SCOPE_WORKFLOW_TEMPLATE => 'bi_constructor_dashboards_bizproc',
			ScopeService::BIC_SCOPE_PROFILE => 'bi_constructor_dashboards_profile',
			ScopeService::BIC_SCOPE_SHOP => 'bi_constructor_dashboards_shop',
			default => 'bi_constructor_dashboards',
		};
	}
}
