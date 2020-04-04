<?php
namespace Bitrix\Sale\BsmSiteMaster\Tools;

use Bitrix\Main;

/**
 * Class SitePatcher
 * @package Bitrix\Sale\BsmSiteMaster\Tools
 */
class SitePatcher
{
	const HIDE_PANEL_FOR_USERS = "hide_panel_for_users";
	const ALL_USERS_ACCESS_CODE = "G2";

	/**
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 */
	public static function unsetG2GroupFromHidePanel()
	{
		$hidePanelForUsers = Main\Config\Option::get("main", self::HIDE_PANEL_FOR_USERS);
		if (CheckSerializedData($hidePanelForUsers) && $hidePanelForUsers = unserialize($hidePanelForUsers))
		{
			$hidePanelForUsers = array_filter($hidePanelForUsers, function($group) {
				return $group !== self::ALL_USERS_ACCESS_CODE;
			});

			Main\Config\Option::set("main", self::HIDE_PANEL_FOR_USERS, serialize($hidePanelForUsers));
		}
	}
}