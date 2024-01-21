<?php

namespace Bitrix\Intranet\Settings\Tools;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

class Sign extends Tool
{
	public function getId(): string
	{
		return 'sign';
	}

	public function getName(): string
	{
		return Loc::getMessage('INTRANET_SETTINGS_TOOLS_SIGN_MAIN') ?? '';
	}

	public function isAvailable(): bool
	{
		return ModuleManager::isModuleInstalled('sign');
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
		return 'menu_sign';
	}

	public function getLeftMenuPath(): ?string
	{
		return '/sign/';
	}

	public function getSettingsPath(): ?string
	{
		return '/sign/config/permission/';
	}
}