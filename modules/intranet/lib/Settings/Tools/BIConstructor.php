<?php

namespace Bitrix\Intranet\Settings\Tools;

use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\Loc;

class BIConstructor extends Tool
{
	public function getId(): string
	{
		return 'crm_bi';
	}

	public function getName(): string
	{
		return Loc::getMessage('INTRANET_SETTINGS_TOOLS_BI_CONSTRUCTOR_MAIN') ?? '';
	}

	public function isAvailable(): bool
	{
		return ModuleManager::isModuleInstalled('biconnector');
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
		return '/bi/dashboard/';
	}

	public function getSettingsPath(): ?string
	{
		return $this->getLeftMenuPath();
	}

	public function getMenuItemId(): ?string
	{
		return 'menu_bi_constructor';
	}
}