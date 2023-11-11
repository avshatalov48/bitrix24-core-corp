<?php

namespace Bitrix\Crm\Component\ControlPanel;

use Bitrix\Main\Web\Json;
use CCrmOwnerType;
use CUserOptions;

class TabsSortHelper
{
	private const MAX_SORT_ID = 100;
	private const WEB_CRM_TABS_USEROPTIONS_NAME = "crm_control_panel_menu";
	private const WEB_CRM_TABS_USEROPTIONS_CATEGORY = "ui";
	private const WEB_CRM_TABS_USEROPTIONS_KEY = "settings";

	public static function getWebCrmTabsOrder(): array
	{
		$userOptionsRaw = CUserOptions::GetOption(self::WEB_CRM_TABS_USEROPTIONS_CATEGORY, self::WEB_CRM_TABS_USEROPTIONS_NAME);
		$result = self::prepareUserOptions($userOptionsRaw, self::WEB_CRM_TABS_USEROPTIONS_KEY);

		return $result;
	}

	public static function getEntityTypeSortIndexInCTRPanelTabs($entityTypeId, $wCrmTabsOrder)
	{
		if (isset($entityTypeId) && !empty($wCrmTabsOrder))
		{
			$fullMenuId = ControlPanelMenuMapper::getCrmTabMenuIdByEntityTypeId($entityTypeId, true, true);
			if (isset($wCrmTabsOrder[$fullMenuId]))
			{
				return $wCrmTabsOrder[$fullMenuId]["sort"];
			}
			if (CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
			{
				$fullParentMenuId = ControlPanelMenuMapper::getParentDynamicMenuId($entityTypeId, true);
			}
			else
			{
				$shortMenuId = ControlPanelMenuMapper::getCrmTabMenuIdByEntityTypeId($entityTypeId);
				$fullParentMenuId = ControlPanelMenuMapper::getParentMenuId($shortMenuId, true);
			}
			if (isset($fullParentMenuId) && isset($wCrmTabsOrder[$fullParentMenuId]))
			{
				return $wCrmTabsOrder[$fullParentMenuId]["sort"];
			}

			return self::MAX_SORT_ID;
		}

		return self::MAX_SORT_ID;
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
