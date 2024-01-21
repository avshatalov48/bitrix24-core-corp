<?php

namespace Bitrix\Meeting\Integration\Intranet;

use Bitrix\Intranet\Settings\Tools\ToolsManager;
use Bitrix\Main\Loader;

final class Settings
{
	public const LIMIT_CODE = 'limit_office_meetings_off';

	private function isAvailable(): bool
	{
		return Loader::includeModule('intranet') && class_exists(ToolsManager::class);
	}

	public function isMeetingsAvailable(): bool
	{
		if (!$this->isAvailable())
		{
			return true;
		}

		return (new ToolsManager())->checkAvailabilityByToolId('meetings');
	}
}