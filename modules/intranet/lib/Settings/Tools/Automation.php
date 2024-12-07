<?php

namespace Bitrix\Intranet\Settings\Tools;

use Bitrix\Bitrix24\Feature;
use Bitrix\Intranet\Site\Sections\AutomationSection;
use Bitrix\Main;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;

class Automation extends Tool
{
	private const AUTOMATION_SUBGROUPS_ID = [
		'robots' => 'menu_robots',
		'bizproc' => 'menu_bizproc_sect',
		'crm-dynamic' => 'menu_crm_dynamic',
		'bizproc_script' => 'menu_bizproc_script',
		'rpa' => 'rpa_tasks',
		'lists' => 'lists',
	];
	private function isListsEnabled(): bool
	{
		return Loader::includeModule('bitrix24') && Feature::isFeatureEnabled('lists');
	}

	private function getBizprocUrl(): string
	{
		return '/bizproc/userprocesses/';
	}

	public function getSubgroupSettingsPath(): array
	{
		return [
			'robots' => '/crm/deal/list/#robots',
			'bizproc' => $this->getBizprocUrl(),
			'crm-dynamic' => $this->getCrmDynamicUrl(),
			'bizproc_script' => '/crm/deal/list/#scripts',
			'rpa' => '/rpa/',
			'lists' => $this->isListsEnabled() ? '/company/lists/' : null,
		];
	}

	private function getCrmDynamicUrl(): string
	{
		static $defaultUrl = '/automation/type/';

		if (!Loader::includeModule('crm'))
		{
			return $defaultUrl;
		}

		$isAutomatedSolutionListEnabled =
			method_exists(\Bitrix\Crm\Settings\Crm::class, 'isAutomatedSolutionListEnabled')
			&& \Bitrix\Crm\Settings\Crm::isAutomatedSolutionListEnabled()
		;
		if (!$isAutomatedSolutionListEnabled)
		{
			return $defaultUrl;
		}

		$router = \Bitrix\Crm\Service\Container::getInstance()->getRouter();
		$automatedSolutionListUrl =
			method_exists($router, 'getAutomatedSolutionListUrl')
				? $router->getAutomatedSolutionListUrl()
				: null
		;
		if (!$automatedSolutionListUrl)
		{
			return $defaultUrl;
		}

		return $automatedSolutionListUrl->getUri();
	}

	public function getSubgroupsInfoHelperSlider(): array
	{
		return [
			'lists' => 'limit_office_records_management',
		];
	}

	public function getId(): string
	{
		return 'automation';
	}

	public function getName(): string
	{
		return Loc::getMessage('INTRANET_SETTINGS_TOOLS_AUTOMATION_MAIN') ?? '';
	}

	public function isAvailable(): bool
	{
		return ModuleManager::isModuleInstalled('bizproc');
	}

	public function enableSubgroup(string $code): void
	{
		if ($this->isListsEnabled())
		{
			if ($this->getSubgroupCode('lists') === $code && !ModuleManager::isModuleInstalled('lists'))
			{
				ModuleManager::add('lists');
				$event = new Event('bitrix24', 'OnManualModuleAddDelete', [
					'modulesList' => ['lists' => 'Y'],
				]);
				$event->send();
				Option::set('bitrix24', 'feature_lists', 'Y');
			}
		}

		if ($this->getSubgroupCode('bizproc_script') === $code)
		{
			$this->clearScriptsCache();
		}

		parent::enableSubgroup($code);
	}

	public function disableSubgroup(string $code): void
	{
		if (Loader::includeModule('bitrix24'))
		{
			if ($this->getSubgroupCode('lists') === $code && ModuleManager::isModuleInstalled('lists'))
			{
				ModuleManager::delete('lists');
				$event = new Event('bitrix24', 'OnManualModuleAddDelete', [
					'modulesList' => ['lists' => 'N'],
				]);
				$event->send();
				Option::set('bitrix24', 'feature_lists', 'N');
			}
		}

		if ($this->getSubgroupCode('bizproc_script') === $code)
		{
			$this->clearScriptsCache();
		}

		parent::disableSubgroup($code);
	}

	public function enableAllSubgroups(): void
	{
		$this->clearScriptsCache();
		parent::enableAllSubgroups();
	}

	public function disableAllSubgroups(): void
	{
		$this->clearScriptsCache();
		parent::disableAllSubgroups();
	}

	public function getSubgroupsIds(): array
	{
		return static::AUTOMATION_SUBGROUPS_ID;
	}

	public function getSubgroups(): array
	{
		$result = [];

		$settingsPath = $this->getSubgroupSettingsPath();
		$infoHelperSlider = $this->getSubgroupsInfoHelperSlider();

		foreach (AutomationSection::getItems() as $item)
		{
			if (
				isset($item['id'], $item['available'], $item['menuData']['menu_item_id'], $item['title'])
				&& $item['id'] === 'lists' ? $this->isListsEnabled() : $item['available']
				&& in_array($item['id'], array_keys(self::AUTOMATION_SUBGROUPS_ID))
			)
			{
				$result[$item['id']] = [
					'name' => Loc::getMessage('INTRANET_SETTINGS_AUTOMATION_TASKS_SUBGROUP_' . strtoupper($item['id'])) ?? $item['title'],
					'id' => $item['id'],
					'code' => $this->getSubgroupCode($item['id']),
					'enabled' => $this->isEnabledSubgroupById($item['id']),
					'menu_item_id' => $item['menuData']['menu_item_id'],
					'settings_path' => $settingsPath[$item['id']] ?? null,
					'settings_title' => $settingsTitle[$item['id']] ?? null,
					'infohelper-slider' => $infoHelperSlider[$item['id']] ?? null,
				];
			}
		}

		return $result;
	}

	public function getMenuItemId(): ?string
	{
		return 'menu_automation';
	}

	public function getSettingsPath(): ?string
	{
		$subgroups = $this->getSubgroups();

		if (!$subgroups['rpa']['enabled'])
		{
			foreach ($subgroups as $subgroup)
			{
				if ($subgroup['enabled'] && $subgroup['settings_path'])
				{
					return $subgroup['settings_path'];
				}
			}
		}

		return '/rpa/';
	}

	private function clearScriptsCache(): void
	{
		if (defined('BX_COMP_MANAGED_CACHE'))
		{
			$cache = Main\Application::getInstance()->getTaggedCache();
			$cache->clearByTag('intranet_menu_binding');
		}
	}
}
