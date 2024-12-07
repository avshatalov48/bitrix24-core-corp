<?php

namespace Bitrix\Intranet\Settings\Tools;

use Bitrix\Bitrix24\Feature;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Tasks\Flow;

class Tasks extends Tool
{
	private const TASKS_SUBGROUP_ID = [
		'base_tasks' => 'menu_tasks',
		'projects' => 'view_projects',
		'flows' => 'flows',
		'scrum' => 'view_scrum',
		'departments' => 'view_departments',
		'effective' => 'view_effective',
		'employee_plan' => 'view_employee_plan',
		'report' => 'view_reports',
		'templates' => 'view_templates',
	];

	private function isTasksPermissionsEnabled(): bool
	{
		return (
			!Loader::includeModule('bitrix24')
			|| Feature::isFeatureEnabled('tasks_access_permissions')
		);
	}

	public function getSubgroupSettingsPath(): array
	{
		return [
			'base_tasks' => '/company/personal/user/#USER_ID#/tasks/',
			'projects' => '/company/personal/user/#USER_ID#/tasks/projects/',
			'flows' => '/company/personal/user/#USER_ID#/tasks/flow/',
			'scrum' => '/company/personal/user/#USER_ID#/tasks/scrum/',
			'departments' => '/company/personal/user/#USER_ID#/tasks/departments/',
			'effective' => '/company/personal/user/#USER_ID#/tasks/effective/',
			'employee_plan' => '/company/personal/user/#USER_ID#/tasks/employee/plan/',
			'report' => '/company/personal/user/#USER_ID#/tasks/report/',
			'templates' => '/company/personal/user/#USER_ID#/tasks/templates/',
		];
	}

	public function getInfoHelperSlider(): ?string
	{
		return 'limit_tasks_access_permissions';
	}

	public function getSettingsTitle(): ?string
	{
		return Loc::getMessage('INTRANET_SETTINGS_TOOLS_TASKS_SETTINGS_TITLE');
	}

	public function getId(): string
	{
		return 'tasks';
	}

	public function getName(): string
	{
		return Loc::getMessage('INTRANET_SETTINGS_TOOLS_TASKS_MAIN') ?? '';
	}

	public function isAvailable(): bool
	{
		return ModuleManager::isModuleInstalled('tasks');
	}

	public function getSubgroupsIds(): array
	{
		return self::TASKS_SUBGROUP_ID;
	}

	public function getSubgroups(): array
	{
		$result = [];

		$settingsPath = $this->getSubgroupSettingsPath();

		foreach (self::TASKS_SUBGROUP_ID as $id => $menuId)
		{
			if ($id === 'flows' && Loader::includeModule('tasks') && !Flow\FlowFeature::isOptionEnabled())
			{
				continue;
			}

			$result[$id] = [
				'name' => Loc::getMessage('INTRANET_SETTINGS_TOOLS_TASKS_SUBGROUP_' . strtoupper($id)),
				'id' => $id,
				'code' => $this->getSubgroupCode($id),
				'enabled' => $this->isEnabledSubgroupById($id),
				'menu_item_id' => $menuId,
				'settings_path' => $settingsPath[$id] ? str_replace("#USER_ID#", CurrentUser::get()->getId(), $settingsPath[$id]) : null,
				'default' => $id === 'base_tasks',
			];
		}

		return $result;
	}

	public function getSettingsPath(): ?string
	{
		return $this->isTasksPermissionsEnabled() ? '/tasks/config/permissions/' : null;
	}

	public function getLeftMenuPath(): ?string
	{
		return '/company/personal/user/#USER_ID#/tasks/';
	}

	public function getMenuItemId(): string
	{
		return 'menu_tasks';
	}
}