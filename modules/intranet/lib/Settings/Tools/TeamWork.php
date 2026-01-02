<?php

namespace Bitrix\Intranet\Settings\Tools;

use Bitrix\Intranet\Integration\Socialnetwork\Collab\CollabProviderData;
use Bitrix\Intranet\UI\LeftMenu\Preset;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

class TeamWork extends Tool
{
	private ?array $sortedSubgroupsId = null;

	private function getSubgroupAvailability($subgroupId): bool
	{
		return match ($subgroupId)
		{
			'instant_messenger' => ModuleManager::isModuleInstalled('im'),
			'collab' => (new CollabProviderData())->isAvailable(),
			'calendar' => ModuleManager::isModuleInstalled('calendar'),
			'docs' => \Bitrix\Main\Config\Option::get('disk', 'documents_enabled', 'N') === 'Y',
			'boards' => \Bitrix\Main\Config\Option::get('disk', 'boards_enabled', 'N') === 'Y',
			'mail' => ModuleManager::isModuleInstalled('mail'),
			default => true,
		};
	}

	protected const TEAMWORK_SUBGROUP_ID = [
		'news' => 'menu_live_feed',
		'instant_messenger' => 'menu_im_messenger',
		'collab' => 'menu_im_collab',
		'workgroups' => 'menu_all_groups',
		'calendar' => 'menu_calendar',
		'docs' => 'menu_documents',
		'boards' => 'menu_boards',
		'disk' => 'menu_files',
		'mail' => 'menu_external_mail',
	];

	public function getSubgroupSettingsPath(): array
	{
		return [
			'news' => '/stream/',
			'instant_messenger' => '/online/',
			'collab' => '/online/?IM_COLLAB',
			'workgroups' => '/workgroups/',
			'calendar' => '/company/personal/user/#USER_ID#/calendar/',
			'docs' => '/company/personal/user/#USER_ID#/disk/documents/',
			'disk' => '/company/personal/user/#USER_ID#/disk/path/',
			'boards' => '/company/personal/user/#USER_ID#/disk/boards/',
			'mail' => '/mail/',
		];
	}

	public function getSubgroupNameById(string $id): string
	{
		return match ($id)
		{
			'collab' =>  Loc::getMessage('INTRANET_SETTINGS_TOOLS_TEAMWORK_SUBGROUP_COLLAB'),
			'instant_messenger' => Loc::getMessage('INTRANET_SETTINGS_TOOLS_TEAMWORK_SUBGROUP_MESSENGER'),
			'news' => Loc::getMessage('INTRANET_SETTINGS_TOOLS_TEAMWORK_SUBGROUP_NEWS_FEED'),
			'disk' => Loc::getMessage('INTRANET_SETTINGS_TOOLS_TEAMWORK_SUBGROUP_DISK_MSGVER_1'),
			default => Loc::getMessage('INTRANET_SETTINGS_TOOLS_TEAMWORK_SUBGROUP_' . strtoupper($id)),
		};
	}

	protected function getSubgroupSettingsTitle(): array
	{
		return [];
	}

	public function getId(): string
	{
		return 'team_work';
	}

	public function getName(): string
	{
		return Loc::getMessage('INTRANET_SETTINGS_TOOLS_TEAMWORK_MAIN') ?? '';
	}

	public function isAvailable(): bool
	{
		return true;
	}

	public function getSubgroupsIds(): array
	{
		return self::TEAMWORK_SUBGROUP_ID;
	}

	public function getSortedSubgroupsIds(): array
	{
		if ($this->sortedSubgroupsId)
		{
			return $this->sortedSubgroupsId;
		}

		$this->sortedSubgroupsId = $this->getSubgroupsIds();
		$activePreset = Preset\Manager::getPreset()->getStructure()['shown'][$this->getMenuItemId()] ?? null;

		if (!empty($activePreset))
		{
			$this->sortedSubgroupsId = array_merge(array_flip($activePreset), array_flip($this->sortedSubgroupsId));
			$this->sortedSubgroupsId = array_flip($this->sortedSubgroupsId);
		}

		return $this->sortedSubgroupsId;
	}

	public function getSubgroups(): array
	{
		$result = [];

		$settingsPath = $this->getSubgroupSettingsPath();
		$settingsTitle = $this->getSubgroupSettingsTitle();

		foreach ($this->getSortedSubgroupsIds() as $id => $menuId)
		{
			// for now we cannot disable disk
			if ($id === 'disk')
			{
				continue;
			}

			if (!isset($this->getSubgroupsIds()[$id]))
			{
				continue;
			}

			if (!$this->getSubgroupAvailability($id))
			{
				continue;
			}

			$result[$id] = [
				'name' => $this->getSubgroupNameById($id),
				'id' => $id,
				'code' => $this->getSubgroupCode($id),
				'enabled' => $this->isEnabledSubgroupById($id),
				'menu_item_id' => $menuId,
				'settings_path' => !empty($settingsPath[$id]) ? str_replace("#USER_ID#", CurrentUser::get()->getId(), $settingsPath[$id]) : null,
				'settings_title' => $settingsTitle[$id] ?? null,
			];
		}

		return $result;
	}

	public function getMenuItemId(): string
	{
		return 'menu_teamwork';
	}

	public function getSettingsPath(): ?string
	{
		foreach ($this->getSubgroups() as $subgroup)
		{
			if ($subgroup['enabled'] && $subgroup['settings_path'])
			{
				return $subgroup['settings_path'];
			}
		}

		return '/stream/';
	}
}
