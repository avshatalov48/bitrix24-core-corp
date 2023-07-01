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

		if (!ModuleManager::isModuleInstalled('socialservices'))
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
			'isConnected' => true, //\Bitrix\Crm\Activity\Provider\Zoom::isConnected(),
			'isAvailable' => !ModuleManager::isModuleInstalled('bitrix24') || \Bitrix\Crm\Activity\Provider\Zoom::isAvailable(),
		];
	}

	public function loadAssets(): void
	{
		global $APPLICATION;
		if ($this->getSettings()['isNotAvailable'] ?? false)
		{
			$APPLICATION->IncludeComponent('bitrix:ui.info.helper', '', []);
		}
	}
}
