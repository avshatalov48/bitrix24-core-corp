<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\Intranet\Service;

use Bitrix\Intranet\Settings\Tools\ToolsManager;
use Bitrix\Main\Loader;

class ToolService
{
	public function isAvailableByToolId(string $toolId): bool
	{
		if (!Loader::includeModule('intranet'))
		{
			return true;
		}

		return (new ToolsManager())->checkAvailabilityByToolId($toolId);
	}

	public function isisAvailableByMenuId(string $menuItemId): bool
	{
		if (!Loader::includeModule('intranet'))
		{
			return true;
		}

		return (new ToolsManager())->checkAvailabilityByMenuId($menuItemId);
	}

	public function isBaseTasksAvailable(): bool
	{
		return $this->isAvailableByToolId(ToolDictionary::BASE_TASKS);
	}

	public function isProjectsAvailable(): bool
	{
		return $this->isAvailableByToolId(ToolDictionary::PROJECTS);
	}

	public function isScrumAvailable(): bool
	{
		return $this->isAvailableByToolId(ToolDictionary::SCRUM);
	}

	public function isDepartmentsAvailable(): bool
	{
		return $this->isAvailableByToolId(ToolDictionary::DEPARTMENTS);
	}

	public function isEffectiveAvailable(): bool
	{
		return $this->isAvailableByToolId(ToolDictionary::EFFECTIVE);
	}

	public function isEmployeePlanAvailable(): bool
	{
		return $this->isAvailableByToolId(ToolDictionary::EMPLOYEE_PLAN);
	}

	public function isReportAvailable(): bool
	{
		return $this->isAvailableByToolId(ToolDictionary::REPORT);
	}

	public function isTemplatesAvailable(): bool
	{
		return $this->isAvailableByToolId(ToolDictionary::TEMPLATES);
	}

	public function isFlowsAvailable(): bool
	{
		return $this->isAvailableByToolId(ToolDictionary::FLOWS);
	}

	public function isCrmBiAvailable(): bool
	{
		return $this->isAvailableByToolId(ToolDictionary::CRM_BI);
	}
}
