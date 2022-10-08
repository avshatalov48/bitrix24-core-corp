<?php

namespace Bitrix\Crm\Service\Timeline\Item;

use Bitrix\Main\Localization\Loc;

final class NotAvailable extends Configurable
{
	public function getType(): string
	{
		return 'NotAvailable';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_ITEM_NOT_AVAILABLE_TITLE');
	}
}
