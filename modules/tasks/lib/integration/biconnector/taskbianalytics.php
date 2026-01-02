<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Integration\BIConnector;

use Bitrix\BIConnector\Superset\Scope\MenuItem\MenuItemCreatorTasks;
use Bitrix\BIConnector\Superset\Scope\ScopeService;
use Bitrix\Main\Loader;

class TaskBIAnalytics
{
	private static ?self $instance = null;

	public static function getInstance(): self
	{
		if (self::$instance === null)
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function getTasksDashboardsMenuItems(): array
	{
		if (!Loader::includeModule('biconnector'))
		{
			return [];
		}

		if (!class_exists(MenuItemCreatorTasks::class))
		{
			return [];
		}

		return ScopeService::getInstance()->prepareScopeMenuItem(
			ScopeService::BIC_SCOPE_TASKS,
		);
	}
}