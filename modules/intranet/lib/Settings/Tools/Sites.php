<?php

namespace Bitrix\Intranet\Settings\Tools;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

class Sites extends Tool
{
	private ?bool $isShopEnabled = null;

	private function isShopEnabled(): bool
	{
		if (is_null($this->isShopEnabled))
		{
			$this->isShopEnabled = Loader::includeModule('crm') && \CCrmSaleHelper::isShopAccess();
		}

		return $this->isShopEnabled;
	}

	public function getId(): string
	{
		return 'sites';
	}

	public function getName(): string
	{
		return $this->isShopEnabled()
			? Loc::getMessage('INTRANET_SETTINGS_TOOLS_SITES_AND_SHOP_MAIN') ?? ''
			: Loc::getMessage('INTRANET_SETTINGS_TOOLS_SITES_MAIN') ?? '';
	}

	public function isAvailable(): bool
	{
		return ModuleManager::isModuleInstalled('landing');
	}

	public function getSubgroupsIds(): array
	{
		return [];
	}

	public function getSubgroups(): array
	{
		return [];
	}

	public function getMenuItemId(): ?string
	{
		return $this->isShopEnabled()
			? 'menu_shop'
			: 'menu_sites';
	}

	public function getAdditionalMenuItemIds(): array
	{
		return [
			'menu_shop',
			'menu_sites'
		];
	}

	public function getSettingsPath(): ?string
	{
		return '/sites/';
	}
}