<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Integration\Templates\Air;

use Bitrix\Intranet\Settings\Tools\ToolsManager;
use Bitrix\Intranet\UI\LeftMenu\Preset\Social;
use Bitrix\Main\Config\Option;
use Bitrix\Main\ModuleManager;

class MenuConverter
{
	public function convert(): bool
	{
		if (!$this->canConvert())
		{
			return false;
		}

		if ($this->isGlobalPresetSocial() && $this->shouldChangeFirstPage())
		{
			$this->setChatGlobalFirstPage();
		}

		Option::set('intranet', 'menu_converted_for_air_template', 'Y');

		return true;
	}

	public function revert(): bool
	{
		if (!$this->canRevert())
		{
			return false;
		}

		$oldFirstPageBySite = Option::get('intranet', 'left_menu_first_page_old', null, 's1');
		$oldFirstPage = Option::get('intranet', 'left_menu_first_page_old', null, 's1');

		if ($oldFirstPageBySite)
		{
			Option::set('intranet', 'left_menu_first_page', $oldFirstPageBySite, 's1');
		}

		if ($oldFirstPage)
		{
			Option::set('intranet', 'left_menu_first_page', $oldFirstPage);
		}

		Option::set('intranet', 'menu_reverted_for_air_template', 'Y');
		\CBitrixComponent::clearComponentCache('bitrix:menu');
		$GLOBALS['CACHE_MANAGER']->cleanDir('menu');
		$GLOBALS['CACHE_MANAGER']->clearByTag('bitrix24_left_menu');

		return true;
	}

	public function convertForUser(int $userId): bool
	{
		if (Option::get('intranet', 'menu_converted_for_air_template', 'N') !== 'Y')
		{
			return false;
		}

		$userPreset = \CUserOptions::GetOption('intranet', 'left_menu_preset_s1', '', $userId);

		if ($userPreset === Social::CODE || $userPreset === '')
		{
			$menu = \CUserOptions::GetOption('intranet', 'left_menu_sorted_items_s1', null, $userId);

			if ($menu && $this->shouldPrioritizeImMessenger($menu))
			{
				\CUserOptions::SetOption('intranet', 'left_menu_sorted_items_old_s1', $menu, false, $userId);
				$menu = $this->prioritizeImMessenger($menu);
				\CUserOptions::SetOption('intranet', 'left_menu_sorted_items_s1', $menu, false, $userId);
				$this->setChatUserFirstPage($userId);
				\Bitrix\Intranet\Composite\CacheProvider::deleteUserCache($userId);

				return true;
			}
		}

		return false;
	}

	public function revertForUser(int $userId): bool
	{
		if (Option::get('intranet', 'menu_reverted_for_air_template', 'N') !== 'Y')
		{
			return false;
		}

		$oldFirstPage = \CUserOptions::GetOption('intranet', 'left_menu_first_page_old_s1', null, $userId);
		$oldMenu = \CUserOptions::GetOption('intranet', 'left_menu_sorted_items_old_s1', null, $userId);

		if (!$oldMenu && !$oldFirstPage)
		{
			return false;
		}

		if ($oldFirstPage)
		{
			\CUserOptions::SetOption('intranet', 'left_menu_first_page_s1', $oldFirstPage, false, $userId);
		}

		if ($oldMenu)
		{
			\CUserOptions::SetOption('intranet', 'left_menu_sorted_items_s1', $oldMenu, false, $userId);
		}

		\Bitrix\Intranet\Composite\CacheProvider::deleteUserCache($userId);

		return true;
	}

	private function canConvert(): bool
	{
		return (
			(ModuleManager::isModuleInstalled('bitrix24') || Option::get('intranet', 'can_converted_menu_box', 'N') === 'Y')
			&& Option::get('intranet', 'menu_converted_for_air_template', 'N') !== 'Y'
			&& ToolsManager::getInstance()->checkAvailabilityByToolId('instant_messenger'))
		;
	}

	private function canRevert(): bool
	{
		return Option::get('intranet', 'menu_converted_for_air_template', 'N') === 'Y'
			&& Option::get('intranet', 'menu_reverted_for_air_template', 'N') !== 'Y';
	}

	private function isGlobalPresetSocial(): bool
	{
		$sitePreset = Option::get('intranet', 'left_menu_preset', '', 's1');
		$globalPreset = Option::get('intranet', 'left_menu_preset');

		return
			$sitePreset === Social::CODE
			|| $globalPreset === Social::CODE
			|| ($sitePreset === '' && $globalPreset === '')
		;
	}

	private function shouldChangeFirstPage(): bool
	{
		$globalFirstPage = Option::get('intranet', 'left_menu_first_page');
		$globalFirstPageBySite = Option::get('intranet', 'left_menu_first_page', '', 's1');

		return
			$globalFirstPage === '/stream/'
			|| $globalFirstPageBySite === '/stream/'
		;
	}

	private function setChatGlobalFirstPage(): void
	{
		$globalFirstPage = Option::get('intranet', 'left_menu_first_page');
		$globalFirstPageBySite = Option::get('intranet', 'left_menu_first_page', '', 's1');

		if ($globalFirstPage === '/stream/')
		{
			Option::set('intranet', 'left_menu_first_page_old', $globalFirstPage);
			Option::set('intranet', 'left_menu_first_page', '/online/');
		}

		if ($globalFirstPageBySite === '/stream/')
		{
			Option::set('intranet', 'left_menu_first_page_old', $globalFirstPageBySite, 's1');
			Option::set('intranet', 'left_menu_first_page', '/online/', 's1');
		}
	}

	private function setChatUserFirstPage(int $userId): void
	{
		$firstPage = \CUserOptions::GetOption('intranet', 'left_menu_first_page_s1', null, $userId);

		if ($firstPage && $firstPage !== '/vibe/')
		{
			\CUserOptions::SetOption('intranet', 'left_menu_first_page_old_s1', $firstPage, false, $userId);
			\CUserOptions::SetOption('intranet', 'left_menu_first_page_s1', '/online/', false, $userId);
		}
	}

	private function shouldPrioritizeImMessenger(array $menu): bool
	{
		$should = true;
		$menuItem = 'menu_im_messenger';

		if (!isset($menu['show']) || !is_array($menu['show']))
		{
			return false;
		}

		if (
			array_key_first($menu['show']) === 'menu_teamwork' &&
			is_array($menu['show']['menu_teamwork']) &&
			!empty($menu['show']['menu_teamwork']) &&
			$menu['show']['menu_teamwork'][0] === $menuItem
		)
		{
			$should = false;
		}
		elseif
		(
			!empty($menu['show']) &&
			array_key_first($menu['show']) === $menuItem
		)
		{
			$should = false;
		}

		return $should;
	}

	private function prioritizeImMessenger(array $menu): array
	{
		$menuItem = 'menu_im_messenger';
		$isFound = false;

		$menu = $this->findAndRemoveMenuItem($menu, $menuItem, $isFound);

		if ($isFound)
		{
			$menu = $this->moveToMenuTop($menu, $menuItem);
		}

		return $menu;
	}

	private function findAndRemoveMenuItem(array $menu, string $menuItem, bool &$isFound): array
	{
		$findAndRemove = static function (array &$array) use (&$findAndRemove, $menuItem, &$isFound) {
			foreach ($array as $key => &$value)
			{
				if ($value === $menuItem)
				{
					unset($array[$key]);
					$isFound = true;

					if (is_int($key))
					{
						$keys = array_keys($array);
						if ($keys === range(0, count($keys) - 1))
						{
							$array = array_values($array);
						}
					}

					return true;
				}

				if (is_array($value) && $findAndRemove($value))
				{
					return true;
				}
			}

			return false;
		};

		$findAndRemove($menu);

		return $menu;
	}

	private function moveToMenuTop(array $menu, string $menuItem): array
	{
		if (
			isset($menu['show'])
			&& is_array($menu['show'])
			&& array_key_first($menu['show']) === 'menu_teamwork'
			&& is_array($menu['show']['menu_teamwork'])
		)
		{
			$menu['show']['menu_teamwork'] = array_merge([$menuItem], $menu['show']['menu_teamwork']);
		}
		else
		{
			$menu['show'] = array_merge([$menuItem], $menu['show']);
		}

		return $menu;
	}
}
