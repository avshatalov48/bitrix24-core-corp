<?php

namespace Bitrix\Timeman\Integration\Intranet;

use Bitrix\Intranet\Settings\Tools\ToolsManager;
use Bitrix\Main\Loader;

final class Settings
{
	public const TOOLS = [
		'worktime' => 'worktime',
		'absence' => 'absence',
	];

	private function isAvailable(): bool
	{
		$toolsManagerClass = '\Bitrix\Intranet\Settings\Tools\ToolsManager';

		return Loader::includeModule('intranet') && class_exists($toolsManagerClass);
	}

	public function isToolAvailable(string $tool): bool
	{
		if (!$this->isAvailable() || !in_array($tool, self::TOOLS))
		{
			return true;
		}

		return (ToolsManager::getInstance())->checkAvailabilityByToolId($tool);
	}
}