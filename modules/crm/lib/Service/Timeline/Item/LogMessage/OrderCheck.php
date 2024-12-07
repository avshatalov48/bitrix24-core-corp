<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

abstract class OrderCheck extends LogMessage
{
	public function getIconCode(): ?string
	{
		return 'check';
	}
}
