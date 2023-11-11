<?php

namespace Bitrix\CrmMobile\CustomSections;

use Bitrix\Crm\Component\ControlPanel;
use Bitrix\Crm\Integration\Intranet\CustomSection;
use Bitrix\Crm\Integration\IntranetManager;
use Bitrix\Main\Web\Json;
use CUserOptions;

class TabSorter
{
	private const MAX_SORT_ID = 100;
	private const CUSTOM_SECTION_TABS_USEROPTIONS_CATEGORY = 'ui';
	private const CUSTOM_SECTION_TABS_USEROPTIONS_KEY = 'settings';

	private const CUSTOM_SECTION_TABS_PREFIX = 'menu_custom_section_';

	public static function getCustomSectionTabsOrder(?CustomSection $customSection): ?array
	{
		if (isset($customSection))
		{
			$customSectionCode = self::getCustomSectionControlPanelCode($customSection->getId());
			if (isset($customSectionCode))
			{
				$userOptionsRaw = CUserOptions::GetOption(
					self::CUSTOM_SECTION_TABS_USEROPTIONS_CATEGORY,
					$customSectionCode
				);
				$result = self::prepareUserOptions($userOptionsRaw, self::CUSTOM_SECTION_TABS_USEROPTIONS_KEY);

				return $result;
			}
		}

		return null;
	}

	public static function getCustomSectionTabSortIndex($entityTypeId, $wTabsOrder, $customSection)
	{
		if (isset($customSection) && isset($entityTypeId) && !empty($wTabsOrder))
		{
			foreach ($customSection->getPages() as $page)
			{
				if (IntranetManager::getEntityTypeIdByPageSettings($page->getSettings()) === $entityTypeId)
				{
					$targetPage = $page;
					break;
				}
			}
			if (isset($targetPage))
			{
				$pageCode = (self::CUSTOM_SECTION_TABS_PREFIX . $customSection->getCode())
					. '_'
					. ($targetPage->getCode());
				foreach ($wTabsOrder as $tabKey => $tabValue)
				{
					if ($tabKey === $pageCode)
					{
						return $tabValue['sort'];
					}
				}
			}
		}

		return self::MAX_SORT_ID;
	}

	public static function sort(array &$mCRMTabs, ?int $customSectionId): void
	{
		if (empty($mCRMTabs))
		{
			return;
		}

		$customSection = null;

		if ($customSectionId !== null)
		{
			$customSection = IntranetManager::getCustomSectionByEntityTypeId($mCRMTabs[0]['id']);
			$wTabsOrder = self::getCustomSectionTabsOrder($customSection);
		}
		else
		{
			$wTabsOrder = ControlPanel\TabsSortHelper::getWebCrmTabsOrder();
		}

		if (empty($wTabsOrder))
		{
			return;
		}

		usort($mCRMTabs, static function ($tab1, $tab2) use ($wTabsOrder, $customSection) {
			if ($customSection)
			{
				$tab1SortIndex = self::getCustomSectionTabSortIndex($tab1['id'], $wTabsOrder, $customSection);
				$tab2SortIndex = self::getCustomSectionTabSortIndex($tab2['id'], $wTabsOrder, $customSection);
			}
			else
			{
				$tab1SortIndex = ControlPanel\TabsSortHelper::getEntityTypeSortIndexInCTRPanelTabs(
					$tab1['id'],
					$wTabsOrder
				);
				$tab2SortIndex = ControlPanel\TabsSortHelper::getEntityTypeSortIndexInCTRPanelTabs(
					$tab2['id'],
					$wTabsOrder
				);
			}
			if ($tab1SortIndex === $tab2SortIndex)
			{
				return 0;
			}

			return ($tab1SortIndex < $tab2SortIndex) ? -1 : 1;
		});
	}

	private static function getCustomSectionControlPanelCode(int $customSectionId): ?string
	{
		$customSections = IntranetManager::getCustomSections();
		if (!empty($customSections))
		{
			foreach ($customSections as $section)
			{
				if ($section->getId() === $customSectionId)
				{
					return self::CUSTOM_SECTION_TABS_PREFIX . $section->getCode();
				}
			}
		}

		return null;
	}

	private static function prepareUserOptions($userOptions, $userOptionsKey): array
	{
		$userOptionsSettings = [];

		if (is_array($userOptions)
			&& !empty($userOptions[$userOptionsKey]))
		{
			$userOptionsSettings = Json::decode($userOptions[$userOptionsKey]);
			if (!is_array($userOptionsSettings))
			{
				$userOptionsSettings = [];
			}
		}

		return $userOptionsSettings;
	}
}
