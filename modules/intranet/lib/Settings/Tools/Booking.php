<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Settings\Tools;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

class Booking extends Tool
{
	public function getId(): string
	{
		return 'booking';
	}

	public function getName(): string
	{
		return Loc::getMessage('INTRANET_SETTINGS_TOOLS_BOOKING_MAIN');
	}

	public function isAvailable(): bool
	{
		return ModuleManager::isModuleInstalled('booking');
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
		return 'menu_booking';
	}

	public function getLeftMenuPath(): ?string
	{
		return '/booking/';
	}

	public function getSettingsPath(): ?string
	{
		return '/booking/';
	}
}
