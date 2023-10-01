<?php

namespace Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;

use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;
use Bitrix\Main\ModuleManager;

class Zoom extends Item
{
	public function getId(): string
	{
		return 'zoom';
	}

	public function getName(): string
	{
		return \Bitrix\Main\Localization\Loc::getMessage('CRM_TIMELINE_ZOOM');
	}

	public function isAvailable(): bool
	{
		if ($this->isCatalogEntityType() || $this->isMyCompany())
		{
			return false;
		}

		if (!ModuleManager::isModuleInstalled('socialservices') || !ModuleManager::isModuleInstalled('bitrix24'))
		{
			return false;
		}

		return true;
	}

	public function hasTariffRestrictions(): bool
	{
		return ModuleManager::isModuleInstalled('bitrix24') && !\Bitrix\Crm\Activity\Provider\Zoom::isAvailable();
	}

	public function prepareSettings(): array
	{
		return [
			'isConnected' => \Bitrix\Crm\Activity\Provider\Zoom::isConnected(),
			'isAvailable' => \Bitrix\Crm\Activity\Provider\Zoom::isAvailable(),
		];
	}

	public function loadAssets(): void
	{
		global $APPLICATION;
		if (!($this->getSettings()['isAvailable'] ?? false))
		{
			$APPLICATION->IncludeComponent('bitrix:ui.info.helper', '', []);
		}
	}
}
