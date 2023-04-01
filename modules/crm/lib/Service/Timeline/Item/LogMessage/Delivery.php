<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__DIR__ . '/../Ecommerce.php');

abstract class Delivery extends LogMessage
{
	public function getType(): string
	{
		return 'Delivery';
	}

	public function getIconCode(): ?string
	{
		return 'taxi';
	}
}
