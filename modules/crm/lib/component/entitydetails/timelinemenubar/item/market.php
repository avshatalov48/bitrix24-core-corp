<?php

namespace Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;

use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;
use Bitrix\Main\UI\Extension;

class Market extends Item
{
	public function getId(): string
	{
		return 'activity_rest_applist';
	}

	public function getName(): string
	{
		return \Bitrix\Main\Localization\Loc::getMessage('CRM_REST_BUTTON_TITLE_2');
	}

	public function isAvailable(): bool
	{
		return \Bitrix\Main\ModuleManager::isModuleInstalled('rest');
	}

	public function prepareSettings(): array
	{
		return [
			'placement' => \Bitrix\Crm\Integration\Rest\AppPlacement::getDetailActivityPlacementCode($this->getEntityTypeId()),
		];
	}

	public function loadAssets(): void
	{
		Extension::load('marketplace');
	}
}
