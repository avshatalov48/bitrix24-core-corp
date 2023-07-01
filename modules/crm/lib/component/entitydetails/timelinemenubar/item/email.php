<?php

namespace Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;

use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;

class Email extends Item
{
	public function getId(): string
	{
		return 'email';
	}

	public function getName(): string
	{
		return \Bitrix\Main\Localization\Loc::getMessage('CRM_TIMELINE_EMAIL');
	}

	public function isAvailable(): bool
	{
		return !$this->isCatalogEntityType() && !$this->isMyCompany();
	}
}
