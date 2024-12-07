<?php

namespace Bitrix\Crm\Service\Timeline\Item\Mixin\CalendarSharing;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__DIR__ . '/.././CalendarSharing.php');

trait MessageTrait
{
	protected function getMessage(string $messageCode, ?array $replace = null): string
	{
		return Loc::getMessage($messageCode, $replace);
	}
}