<?php

namespace Bitrix\Intranet\Settings\Tools;

use Bitrix\Intranet\Portal\FirstPage;
use Bitrix\Intranet\UI\LeftMenu\Preset\Manager;
use Bitrix\Intranet\UI\LeftMenu\Preset\PresetAbstract;
use Bitrix\Main\Config\Option;

class FirstPageChanger
{
	private FirstPage $firstPage;

	public function __construct(private ToolsManager $toolsManager)
	{
		$this->firstPage = FirstPage::getInstance();
	}

	public function changeForAllUsers(string $siteId = SITE_ID): void
	{
		$presetId = Option::get('intranet', 'left_menu_preset', '', $siteId);
		$preset = Manager::getPreset($presetId, $siteId);
		$structure = [];

		if ($preset instanceof PresetAbstract)
		{
			$structure = $preset->getToolsStructure() ?? $preset->getStructure();
		}

		$firstPage = $this->getAvailableFirstPage($structure, $siteId);

		if ($firstPage)
		{
			Option::set('intranet', 'left_menu_first_page', $firstPage, $siteId);
			\CUserOptions::DeleteOptionsByName('intranet', 'left_menu_first_page_changed_' . $siteId);
		}

		$this->firstPage->clearCacheForAll();
	}

	public function changeForCurrentUser($structure): void
	{
		$firstPage = '';

		foreach ($structure as $menuItem)
		{
			if ($menuItem['ID'] && $this->toolsManager->checkAvailabilityByMenuId($menuItem['ID']))
			{
				if (isset($menuItem['PARAMS']['real_link']) && is_string($menuItem['PARAMS']['real_link']))
				{
					$firstPage = $menuItem['PARAMS']['real_link'];

					break;
				}

				if (!$firstPage && isset($menuItem['LINK']) && is_string($menuItem['LINK']))
				{
					$firstPage = $menuItem['LINK'];

					break;
				}
			}
		}

		if ($firstPage)
		{
			\CUserOptions::SetOption('intranet', 'left_menu_first_page_' . SITE_ID, $firstPage);
			\CUserOptions::SetOption('intranet', 'left_menu_first_page_changed_' . SITE_ID, 'Y');
		}

		$this->firstPage->clearCache();
	}

	public function checkNeedChanges(): bool
	{
		$isFirstPageChanged = \CUserOptions::GetOption('intranet', 'left_menu_first_page_changed_' . SITE_ID, 'N') === 'Y';
		$userSortedItems = \CUserOptions::GetOption('intranet', 'left_menu_sorted_items_' . SITE_ID, 'N') !== 'N';
		$userPreset = \CUserOptions::GetOption('intranet', 'left_menu_preset_' . SITE_ID, '');
		$sitePreset = Option::get('intranet', 'left_menu_preset', '', SITE_ID);

		return !$isFirstPageChanged && ($userSortedItems || ($userPreset && $userPreset !== $sitePreset));
	}

	private function getAvailableFirstPage(array $structure, string $siteId = SITE_ID): ?string
	{
		$menuId = is_array($structure['shown']) ? $this->getAvailableMenuIdFromStructure($structure['shown']) : null;
		$availableUrl = $menuId ? $this->getUrlByMenuId($menuId) : null;

		if (!$availableUrl)
		{
			$menuUser = new \Bitrix\Intranet\UI\LeftMenu\User();

			foreach (Manager::getPreset(siteId: $siteId)->getItems() as $item)
			{
				$customItem = $item->prepareData($menuUser);

				if (
					isset($customItem['ID'])
					&& is_string($customItem['LINK'])
					&& $this->toolsManager->checkAvailabilityByMenuId($customItem['ID'])
				)
				{
					$availableUrl = $customItem['LINK'];

					break;
				}
			}
		}

		return $availableUrl;
	}

	private function getUrlByMenuId(string $menuItemId): ?string
	{
		$tools = $this->toolsManager->getToolList();

		foreach ($tools as $id => $tool)
		{
			if ($id === $menuItemId || $tool->getMenuItemId() === $menuItemId || in_array($menuItemId, $tool->getAdditionalMenuItemIds()))
			{
				return $tool->getLeftMenuPath();
			}

			$subgroupIds = $tool->getSubgroupsIds();
			$subgroupPaths = $tool->getSubgroupSettingsPath();

			foreach ($subgroupIds as $subgroupId => $subgroupMenuId)
			{
				if ($subgroupMenuId === $menuItemId)
				{
					return $subgroupPaths[$subgroupId];
				}
			}
		}

		return null;
	}

	private function getAvailableMenuIdFromStructure(array $structure): ?string
	{
		foreach ($structure as $key => $menuItem)
		{
			if (is_array($menuItem))
			{
				if (is_string($key) && $this->toolsManager->checkAvailabilityByMenuId($key))
				{
					return $key;
				}
			}
			elseif (is_string($menuItem) && $this->toolsManager->checkAvailabilityByMenuId($menuItem))
			{
				return $menuItem;
			}
		}

		return null;
	}
}