<?php

namespace Bitrix\Tasks\Integration\Intranet;

use Bitrix\Intranet\Settings\Tools\ToolsManager;
use Bitrix\Main\Loader;

final class Settings
{
	public const TOOLS = [
		'base_tasks' => 'base_tasks',
		'projects' => 'projects',
		'scrum' => 'scrum',
		'departments' => 'departments',
		'effective' => 'effective',
		'employee_plan' => 'employee_plan',
		'report' => 'report',
		'templates' => 'templates',
		'flows' => 'flows',
	];

	private function isAvailable(): bool
	{
		return Loader::includeModule('intranet') && class_exists(ToolsManager::class);
	}

	public function isToolAvailable(string $tool): bool
	{
		if (!$this->isAvailable() || !array_key_exists($tool, self::TOOLS))
		{
			return true;
		}

		return (new ToolsManager())->checkAvailabilityByToolId($tool);
	}
}