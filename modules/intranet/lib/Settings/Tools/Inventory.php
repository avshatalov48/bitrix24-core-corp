<?php

namespace Bitrix\Intranet\Settings\Tools;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

class Inventory extends Tool
{

	public function getId(): string
	{
		return 'inventory_management';
	}

	public function getName(): string
	{
		return Loc::getMessage('INTRANET_SETTINGS_TOOLS_INVENTORY_MANAGEMENT_MAIN') ?? '';
	}

	public function isAvailable(): bool
	{
		return ModuleManager::isModuleInstalled('catalog');
	}

	public function getSubgroupsIds(): array
	{
		return [];
	}

	public function getSubgroups(): array
	{
		return [];
	}

	public function getLeftMenuPath(): ?string
	{
		return '/shop/documents/?inventoryManagementSource=inventory';
	}

	public function getSettingsPath(): ?string
	{
		return '/shop/documents/?inventoryManagementSource=inventory';
	}

	public function getMenuItemId(): ?string
	{
		return 'menu_crm_store';
	}
}