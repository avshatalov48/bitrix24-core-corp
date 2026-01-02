<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Integration\Intranet;

use Bitrix\Intranet\Settings\Tools\ToolsManager;
use Bitrix\Main\Loader;

class BookingTool
{
	public function isDisabled(): bool
	{
		return Loader::includeModule('intranet')
			&& ToolsManager::getInstance()->checkAvailabilityByToolId('booking') === false
		;
	}
}
