<?php

declare(strict_types=1);

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\Booking;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Main\Localization\Loc;

class WaitListItemCreated extends LogMessage
{
	public function getType(): string
	{
		return 'WaitListItemCreated';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_LOG_WAIT_LIST_ITEM_CREATED_TITLE');
	}
}
