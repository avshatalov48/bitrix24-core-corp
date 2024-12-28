<?php

namespace Bitrix\Intranet\Settings\Tools;

use Bitrix\Bitrix24\Feature;
use Bitrix\Intranet\Site\Sections\TimemanSection;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

class Company extends Tool
{
	private function getSubgroupsAvailability($subgroupId): bool
	{
		return match ($subgroupId) {
			'knowledge_base' => ModuleManager::isModuleInstalled('landing'),
			'video_conference' => ModuleManager::isModuleInstalled('im'),
			'worktime', 'work_report', 'schedules' =>
				ModuleManager::isModuleInstalled('timeman')
				|| (Loader::includeModule('bitrix24') && Feature::isFeatureEnabled('timeman')),
			'meetings' =>
				ModuleManager::isModuleInstalled('meeting')
				|| (Loader::includeModule('bitrix24') && Feature::isFeatureEnabled('meeting')),
			default => true,
		};
	}

	protected const COMPANY_SUBGROUPS_ID = [
		'structure' => '',
		'employees' => '',
		'knowledge_base' => 'menu_knowledge',
		'video_conference' => 'menu_conference',
		'meetings' => 'menu_meeting',
	];

	protected const TIMEMAN_SUBGROUPS_ID = [
		'worktime' => 'menu_timeman',
		'absence' => 'menu_absence',
		'login_history' => 'menu_login_history',
	];

	protected const SUBGROUPS_ORDER_ARRAY = [
		'structure', 'employees', 'worktime', 'absence', 'knowledge_base', 'meetings', 'login_history', 'video_conference'
	];

	public function getId(): string
	{
		return 'company';
	}

	public function getName(): string
	{
		return Loc::getMessage('INTRANET_SETTINGS_TOOLS_COMPANY_MAIN') ?? '';
	}

	public function isAvailable(): bool
	{
		return true;
	}

	public function isDefault(): bool
	{
		return true;
	}

	public function getSubgroupsIds(): array
	{
		return array_merge(self::COMPANY_SUBGROUPS_ID, self::TIMEMAN_SUBGROUPS_ID);
	}

	public function isEnabledSubgroupById(string $id): bool
	{
		if ('worktime' === $id && !ModuleManager::isModuleInstalled('timeman') && !ModuleManager::isModuleInstalled('bitrix24'))
		{
			return false;
		}

		if ('meetings' === $id && !ModuleManager::isModuleInstalled('meeting') && !ModuleManager::isModuleInstalled('bitrix24'))
		{
			return false;
		}

		return parent::isEnabledSubgroupById($id);
	}

	public function enableSubgroup(string $code): void
	{
		if (Loader::includeModule('bitrix24'))
		{
			if ($this->getSubgroupCode('worktime') === $code && Feature::isFeatureEnabled('timeman') && !ModuleManager::isModuleInstalled('timeman'))
			{
				ModuleManager::add('timeman');
				$event = new Event('bitrix24', 'OnManualModuleAddDelete', [
					'modulesList' => ['timeman' => 'Y'],
				]);
				$event->send();
				Option::set('bitrix24', 'feature_timeman', 'Y');
			}

			if ($this->getSubgroupCode('meetings') === $code && Feature::isFeatureEnabled('meeting') && !ModuleManager::isModuleInstalled('meeting'))
			{
				ModuleManager::add('meeting');
				$event = new Event('bitrix24', 'OnManualModuleAddDelete', [
					'modulesList' => ['meeting' => 'Y'],
				]);
				$event->send();
				Option::set('bitrix24', 'feature_meeting', 'Y');
			}
		}

		parent::enableSubgroup($code);
	}

	public function disableSubgroup(string $code): void
	{
		if (Loader::includeModule('bitrix24'))
		{
			if ($this->getSubgroupCode('worktime') === $code && Feature::isFeatureEnabled('timeman') && ModuleManager::isModuleInstalled('timeman'))
			{
				ModuleManager::delete('timeman');
				$event = new Event('bitrix24', 'OnManualModuleAddDelete', [
					'modulesList' => ['timeman' => 'N'],
				]);
				$event->send();
				Option::set('bitrix24', 'feature_timeman', 'N');
			}

			if ($this->getSubgroupCode('meetings') === $code && Feature::isFeatureEnabled('meeting') && ModuleManager::isModuleInstalled('meeting'))
			{
				ModuleManager::delete('meeting');
				$event = new Event('bitrix24', 'OnManualModuleAddDelete', [
					'modulesList' => ['meeting' => 'N'],
				]);
				$event->send();
				Option::set('bitrix24', 'feature_meeting', 'N');
			}
		}

		parent::disableSubgroup($code);
	}

	public function getSubgroupSettingsPath(): array
	{
		return [
			'structure' => '/hr/structure/',
			'employees' => '/company/',
			'worktime' => '/timeman/timeman.php',
			'knowledge_base' => '/kb/',
			'video_conference' => '/conference/',
			'meetings' => '/timeman/meeting/',
			'absence' => '/timeman/',
		];
	}

	protected function getSubgroupSettingsTitle(): array
	{
		return [];
	}

	protected function getSubgroupsInfoHelperSlider(): array
	{
		return [
			'login_history' => 'limit_office_login_history',
		];
	}

	public function getSubgroups(): array
	{
		$result = [];

		$settingsPath = $this->getSubgroupSettingsPath();
		$settingsTitle = $this->getSubgroupSettingsTitle();
		$infoHelperSlider = $this->getSubgroupsInfoHelperSlider();

		foreach (self::COMPANY_SUBGROUPS_ID as $id => $menuId)
		{
			if (!$this->getSubgroupsAvailability($id))
			{
				continue;
			}

			$result[$id] = [
				'name' => Loc::getMessage('INTRANET_SETTINGS_TOOLS_COMPANY_SUBGROUP_' . strtoupper($id)),
				'id' => $id,
				'code' => $this->getSubgroupCode($id),
				'enabled' => $this->isEnabledSubgroupById($id),
				'menu_item_id' => $menuId,
				'available' => true,
				'settings_path' => $settingsPath[$id] ?? null,
				'settings_title' => $settingsTitle[$id] ?? null,
				'infohelper-slider' => $infoHelperSlider[$id] ?? null,
				'disabled' => $id === 'structure' || $id === 'employees',
			];
		}

		$timemanItems = TimemanSection::getItems();

		foreach ($timemanItems as $item)
		{
			if (
				isset($item['id'], $item['available'], $item['menuData']['menu_item_id'], $item['title'])
				&& $item['available']
				// && $this->getSubgroupsAvailability($item['id'])
			)
			{
				if (in_array($item['id'], array_keys(self::TIMEMAN_SUBGROUPS_ID)))
				{
					$result[$item['id']] = [
						'name' => Loc::getMessage('INTRANET_SETTINGS_TOOLS_COMPANY_SUBGROUP_' . strtoupper($item['id'])) ?? $item['title'],
						'id' => $item['id'],
						'code' => $this->getSubgroupCode($item['id']),
						'enabled' => $this->isEnabledSubgroupById($item['id']),
						'menu_item_id' => $item['menuData']['menu_item_id'],
						'settings_path' => $settingsPath[$item['id']] ?? $item['url'] ?? null,
						'settings_title' => $settingsTitle[$item['id']] ?? null,
						'infohelper-slider' => $infoHelperSlider[$item['id']] ?? null,
					];
				}
				elseif (in_array($item['id'], ['work_report', 'schedules']))
				{
					$result[$item['id']] = [
						'name' => null,
						'code' => $this->getSubgroupCode('worktime'),
						'enabled' => $this->isEnabledSubgroupById('worktime'),
						'menu_item_id' => $item['menuData']['menu_item_id'],
					];
				}
			}
		}

		$result = array_replace(array_flip(self::SUBGROUPS_ORDER_ARRAY), $result);
		$result = array_filter($result, function ($value) {
			return is_array($value);
		});

		return $result;
	}

	public function getMenuItemId(): ?string
	{
		return 'menu_company';
	}

	public function getSettingsPath(): ?string
	{
		return '/hr/structure/';
	}
}