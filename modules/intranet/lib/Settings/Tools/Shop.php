<?php

namespace Bitrix\Intranet\Settings\Tools;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

class Shop extends Tool
{

	public function getId(): string
	{
		return 'shop';
	}

	public function getName(): string
	{
		return Loc::getMessage('INTRANET_SETTINGS_TOOLS_SHOP_MAIN') ?? '';
	}

	public function isAvailable(): bool
	{
		return ModuleManager::isModuleInstalled('crm') && ModuleManager::isModuleInstalled('catalog');
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
		return 'menu_shop';
	}

	public function getSettingsPath(): ?string
	{
		return '/shop/stores/';
	}
}