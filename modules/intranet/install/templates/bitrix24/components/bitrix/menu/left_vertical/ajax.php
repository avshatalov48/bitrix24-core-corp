<?
use Bitrix\Socialnetwork\Item\WorkgroupFavorites;

define("NOT_CHECK_PERMISSIONS", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
IncludeModuleLangFile(__FILE__);
global $USER;

$setGroupToFavorites = function($groupId, $value = "Y")
{
	if (intval($groupId) && $GLOBALS["USER"]->getId() && CModule::IncludeModule("socialnetwork"))
	{
		try
		{
			WorkgroupFavorites::set(array(
				"GROUP_ID" => intval($groupId),
				"USER_ID" => $GLOBALS["USER"]->getId(),
				"VALUE" => $value === "Y" ? "Y" : "N"
			));
		}
		catch (Exception $e)
		{

		}
	}
};

if ($_SERVER["REQUEST_METHOD"]=="POST" && $_POST["action"] <> '' && check_bitrix_sessid())
{
	if (Bitrix\Main\Loader::includeModule("intranet"))
	{
		Bitrix\Intranet\Composite\CacheProvider::deleteUserCache();
	}

	if (defined("BX_COMP_MANAGED_CACHE"))
	{
		global $CACHE_MANAGER;
		$CACHE_MANAGER->ClearByTag("bitrix24_left_menu");
	}

	$isAdmin = $GLOBALS["USER"]->isAdmin() ||
		(\Bitrix\Main\Loader::includeModule("bitrix24") && \CBitrix24::IsPortalAdmin($GLOBALS["USER"]->getID()));

	CUtil::JSPostUnescape();

	if (isset($_POST["site_id"]) && trim($_POST["site_id"]))
	{
		$siteID = trim($_POST["site_id"]);
	}
	else
	{
		$dbSite = CSite::GetList("sort", "desc", array("DEFAULT"=>"Y"));
		if ($arSite = $dbSite->Fetch())
		{
			$siteID = $arSite["LID"];
		}
	}

	$optionName = "left_menu_sorted_items_".$siteID;
	$optionNameUserFavoriteItems = "user_added_favorite_items_".$siteID;
	$error = "";

	$menuItemID = trim($_POST["menu_item_id"]);
	$userOption = CUserOptions::GetOption("intranet", $optionName);

	$arJsonData = array();

	switch ($_POST["action"])
	{
		case "add_standard_item":
			$itemLink = $itemText = "";

			if (isset($_POST["itemData"]["text"]))
			{
				$itemText = trim($_POST["itemData"]["text"]);
			}
			if (empty($itemText))
			{
				$error = GetMessage("LEFT_MENU_SELF_ITEM_TEXT_ERROR");
			}

			if (isset($_POST["itemData"]["link"]))
			{
				$itemLink = trim($_POST["itemData"]["link"]);
				if (!preg_match("~^/~i", $itemLink))
					$error = GetMessage("LEFT_MENU_SELF_ITEM_LINK_ERROR");
			}

			if (isset($_POST["itemData"]["id"]))
			{
				$itemId = trim($_POST["itemData"]["id"]);
			}
			else
			{
				$itemId = crc32($itemLink);
			}

			if (!empty($error))
				break;

			$newItem = array(
				"TEXT" => $itemText,
				"LINK" => $itemLink,
				"ID" => $itemId
			);

			if (isset($_POST["itemData"]["counterId"]) && $_POST["itemData"]["counterId"])
			{
				$newItem["COUNTER_ID"] = $_POST["itemData"]["counterId"];
			}

			if (isset($_POST["itemData"]["subLink"]) && is_array($_POST["itemData"]["subLink"]))
			{
				$newItem["SUB_LINK"] = $_POST["itemData"]["subLink"]["URL"];
			}

			$standardItems = CUserOptions::GetOption("intranet", "left_menu_standard_items_".$siteID);

			if (is_array($standardItems) && !empty($standardItems))
			{
				foreach ($standardItems as $item)
				{
					if ($item["LINK"] == $newItem["LINK"])
					{
						$error = GetMessage("LEFT_MENU_SELF_ITEM_DUBLICATE_ERROR");
						break 2;
					}
				}
				$standardItems[$itemId] = $newItem;
			}
			else
			{
				$standardItems = array($itemId => $newItem);
			}

			CUserOptions::SetOption("intranet", "left_menu_standard_items_".$siteID, $standardItems);

			if (preg_match("~^/workgroups/group/([0-9]+)/$~i", $itemLink, $match))
			{
				$setGroupToFavorites($match[1], "Y");
			}

			$arJsonData["itemId"] = $itemId;

			break;

		case "delete_standard_item":
			$standardItems = CUserOptions::GetOption("intranet", "left_menu_standard_items_".$siteID);
			if (is_array($standardItems))
			{
				$itemId = "";
				if (isset($_POST["itemData"]["link"]))
				{
					$itemId = crc32($_POST["itemData"]["link"]);
				}
				else if (isset($_POST["itemData"]["id"]))
				{
					$itemId = $_POST["itemData"]["id"];
				}

				$arJsonData["itemId"] = $itemId;

				if (!$itemId)
					break;

				$itemLink = "";
				foreach($standardItems as $key => $item)
				{
					if ($item["ID"] == $itemId)
					{
						$itemLink = $item["LINK"];
						unset($standardItems[$key]);
						break;
					}
				}

				if (preg_match("~^/workgroups/group/([0-9]+)/$~i", $itemLink, $match))
				{
					$setGroupToFavorites($match[1], "N");
				}

				if (!empty($standardItems))
				{
					CUserOptions::SetOption("intranet", "left_menu_standard_items_".$siteID, $standardItems);
				}
				else
				{
					CUserOptions::DeleteOption("intranet", "left_menu_standard_items_".$siteID);
				}

			}
			break;

		case "update_standard_item":

			if (isset($_POST["itemId"]))
			{
				$itemId = $_POST["itemId"];
			}
			else
			{
				$error = GetMessage("LEFT_MENU_SELF_ITEM_UNKNOWN_ERROR");
				break;
			}

			$itemText = "";
			if (isset($_POST["itemText"]))
			{
				$itemText = trim($_POST["itemText"]);
			}
			if (empty($itemText))
			{
				$error = GetMessage("LEFT_MENU_SELF_ITEM_TEXT_ERROR");
				break;
			}

			$standardItems = CUserOptions::GetOption("intranet", "left_menu_standard_items_".$siteID);
			if (is_array($standardItems))
			{
				foreach($standardItems as $key => $item)
				{
					if ($item["ID"] == $itemId)
					{
						$standardItems[$key]["TEXT"] = $itemText;
						break;
					}
				}

				if (!empty($standardItems))
				{
					CUserOptions::SetOption("intranet", "left_menu_standard_items_".$siteID, $standardItems);
				}
				else
				{
					CUserOptions::DeleteOption("intranet", "left_menu_standard_items_".$siteID);
				}

			}

			break;

		case "add_self_item":

			$itemLink = $itemText = "";

			if (!isset($_POST["itemData"]))
				$error = GetMessage("LEFT_MENU_SELF_ITEM_UNKNOWN_ERROR");

			if (isset($_POST["itemData"]["text"]))
			{
				$itemText = trim($_POST["itemData"]["text"]);
				$itemText = \Bitrix\Main\Text\Emoji::encode($itemText);
			}
			if (empty($itemText))
			{
				$error = GetMessage("LEFT_MENU_SELF_ITEM_EMPTY_ERROR");
			}

			if (isset($_POST["itemData"]["link"]))
			{
				$itemLink = trim($_POST["itemData"]["link"]);
				if (!preg_match("~^[/|http]~i", $itemLink))
					$error = GetMessage("LEFT_MENU_SELF_ITEM_LINK_ERROR");
			}

			if (!empty($error))
				break;

			$itemID = crc32($itemLink);
			$newItem = array(
				"TEXT" => $itemText,
				"LINK" => $itemLink,
				"ID" => $itemID,
				"NEW_PAGE" => isset($_POST["itemData"]["openInNewPage"]) && $_POST["itemData"]["openInNewPage"] == "Y" ? "Y" : "N"
			);
			$selfItems = CUserOptions::GetOption("intranet", "left_menu_self_items_".$siteID);

			if (is_array($selfItems) && !empty($selfItems))
			{
				foreach ($selfItems as $item)
				{
					if ($item["LINK"] == $newItem["LINK"])
					{
						$error = GetMessage("LEFT_MENU_SELF_ITEM_DUBLICATE_ERROR");
						break 2;
					}
				}
				$selfItems[] = $newItem;
			}
			else
			{
				$selfItems = array($newItem);
			}
			CUserOptions::SetOption("intranet", "left_menu_self_items_".$siteID, $selfItems);

			$arJsonData["itemId"] = crc32($itemLink);
			break;

		case "update_self_item":
			if (!isset($_POST["itemData"]))
				$error = GetMessage("LEFT_MENU_SELF_ITEM_UNKNOWN_ERROR");

			$itemData = array(
				"ID" => $_POST["itemData"]["id"],
				"NEW_PAGE" => isset($_POST["itemData"]["openInNewPage"]) && $_POST["itemData"]["openInNewPage"] == "Y" ? "Y" : "N"
			);

			if (isset($_POST["itemData"]["text"]))
			{
				$itemData["TEXT"] = trim($_POST["itemData"]["text"]);
				$itemData["TEXT"] = \Bitrix\Main\Text\Emoji::encode($itemData["TEXT"]);
			}
			if (empty($itemData["TEXT"]))
			{
				$error = GetMessage("LEFT_MENU_SELF_ITEM_EMPTY_ERROR");
			}

			if (isset($_POST["itemData"]["link"]))
			{
				$itemData["LINK"] = trim($_POST["itemData"]["link"]);
				if (!preg_match("~^[/|http]~i", $itemData["LINK"]))
					$error = GetMessage("LEFT_MENU_SELF_ITEM_LINK_ERROR");
			}

			if (!empty($error))
				break;

			$selfItems = CUserOptions::GetOption("intranet", "left_menu_self_items_".$siteID);
			if (is_array($selfItems) && !empty($selfItems))
			{
				foreach ($selfItems as $key => $item)
				{
					if ($item["ID"] == $_POST["itemData"]["id"])
					{
						$selfItems[$key] = $itemData;

						CUserOptions::SetOption("intranet", "left_menu_self_items_".$siteID, $selfItems);
						break;
					}
				}
			}

			break;

		case "delete_self_item":
			if (isset($_POST["menu_item_id"]))
			{
				$itemId = $_POST["menu_item_id"];
				$selfItems = CUserOptions::GetOption("intranet", "left_menu_self_items_".$siteID);
				if (is_array($selfItems))
				{
					foreach ($selfItems as $key => $item)
					{
						if ($item["ID"] == $itemId)
						{
							unset($selfItems[$key]);
							break;
						}
					}

					if (!empty($selfItems))
					{
						CUserOptions::SetOption("intranet", "left_menu_self_items_".$siteID, $selfItems);
					}
					else
					{
						CUserOptions::DeleteOption("intranet", "left_menu_self_items_".$siteID);
					}
				}
			}
			break;

		/*case "get_app_rigths":
			if (CModule::IncludeModule("bitrix24"))
			{
				if (isset($_POST["app_id"]) && intval($_POST["app_id"]))
					$app_id = intval($_POST["app_id"]);

				$resRights = CBitrix24AppRights::GetAppRights($app_id);
				$arJsonData["rights"] = $resRights;
			}
			break;
		case "set_app_rights":
			if (CModule::IncludeModule("bitrix24") && $GLOBALS['USER']->CanDoOperation('bitrix24_config'))
			{
				if (isset($_POST["app_id"]) && intval($_POST["app_id"]))
					$app_id = intval($_POST["app_id"]);

				$resRights = CBitrix24AppRights::SetAppRights($app_id, $_POST["rights"]);
			}
			break;
		*/
		case "add_item_to_all":
			if (!$isAdmin)
				break;

			if (isset($_POST["itemInfo"]) && is_array($_POST["itemInfo"]))
			{
				$itemText = trim($_POST["itemInfo"]["text"]);
				$itemText = \Bitrix\Main\Text\Emoji::encode($itemText);

				$itemData = array(
					"TEXT" => $itemText,
					"LINK" => $_POST["itemInfo"]["link"],
					"ID" => $_POST["itemInfo"]["id"],
				);
				if (isset($_POST["itemInfo"]["openInNewPage"]) && $_POST["itemInfo"]["openInNewPage"] == "Y")
				{
					$itemData["NEW_PAGE"] = "Y";
				}

				if (!empty($_POST["itemInfo"]["counterId"]))
					$itemData["COUNTER_ID"] = $_POST["itemInfo"]["counterId"];

				$adminOption = COption::GetOptionString("intranet", "left_menu_items_to_all_".$siteID, "", $siteID);

				if (!empty($adminOption))
				{
					$adminOption = unserialize($adminOption, ["allowed_classes" => false]);
					foreach ($adminOption as $item)
					{
						if ($item["ID"] == $itemData["ID"])
							break 2;
					}
					$adminOption[] = $itemData;
				}
				else
				{
					$adminOption = array($itemData);
				}

				COption::SetOptionString("intranet", "left_menu_items_to_all_".$siteID, serialize($adminOption), false, $siteID);
			}

			break;

		case "delete_item_from_all":
			if (!$isAdmin)
				break;

			if (!isset($_POST["menu_item_id"]))
				break;

			$adminOption = COption::GetOptionString("intranet", "left_menu_items_to_all_".$siteID, "", $siteID);

			if (!empty($adminOption))
			{
				$adminOption = unserialize($adminOption, ["allowed_classes" => false]);
				foreach ($adminOption as $key => $item)
				{
					if ($item["ID"] == $_POST["menu_item_id"])
					{
						unset($adminOption[$key]);
						if (empty($adminOption))
						{
							COption::RemoveOption("intranet", "left_menu_items_to_all_".$siteID);
						}
						else
						{
							COption::SetOptionString("intranet", "left_menu_items_to_all_".$siteID, serialize($adminOption), false, $siteID);
						}

						break 2;
					}
				}
			}

			break;

		case "delete_custom_item_from_all":
			if (!$isAdmin || !isset($_POST["menu_item_id"]))
				break;

			$customItems = COption::GetOptionString("intranet", "left_menu_custom_preset_items", "", $siteID);

			if (!empty($customItems))
			{
				$customItems = unserialize($customItems, ["allowed_classes" => false]);
				foreach ($customItems as $key => $item)
				{
					if ($item["ID"] == $_POST["menu_item_id"])
					{
						unset($customItems[$key]);
						if (empty($customItems))
						{
							COption::RemoveOption("intranet", "left_menu_custom_preset_items", $siteID);
						}
						else
						{
							COption::SetOptionString("intranet", "left_menu_custom_preset_items", serialize($customItems), false, $siteID);
						}

						break;
					}
				}
			}

			$customItemsSort = COption::GetOptionString("intranet", "left_menu_custom_preset_sort", "", $siteID);
			if (!empty($customItemsSort))
			{
				$customItemsSort = unserialize($customItemsSort, ["allowed_classes" => false]);
				foreach (array("show", "hide") as $status)
				{
					foreach ($customItemsSort[$status] as $key=>$itemId)
					{
						if ($itemId == $_POST["menu_item_id"])
						{
							unset($customItemsSort[$status][$key]);
						}
					}
				}

				COption::SetOptionString("intranet", "left_menu_custom_preset_sort", serialize($customItemsSort), false, $siteID);
			}

			break;

		case "add_favorite_admin":
			if ($isAdmin)
			{
				$adminOption = COption::GetOptionString("intranet", "admin_menu_items", "", $siteID);
				if ($adminOption)
					$adminOption = unserialize($adminOption, ["allowed_classes" => false]);
				else
					$adminOption = array();
				if (is_array($adminOption) && !in_array($menuItemID, $adminOption))
				{
					$adminOption[] = $menuItemID;
					$adminOption = serialize($adminOption);
					COption::SetOptionString("intranet", "admin_menu_items", $adminOption, false, $siteID);
				}
			}
			else
			{
				$error = "Y";
			}
			break;

		case "delete_favorite_admin":
			if ($isAdmin)
			{
				$adminOption = COption::GetOptionString("intranet", "admin_menu_items", "", $siteID);
				if ($adminOption)
					$adminOption = unserialize($adminOption, ["allowed_classes" => false]);
				else
					$adminOption = array();
				if (is_array($adminOption) && in_array($menuItemID, $adminOption))
				{
					$key = array_search($menuItemID, $adminOption);
					unset($adminOption[$key]);
					$adminOption = serialize($adminOption);
					COption::SetOptionString("intranet", "admin_menu_items", $adminOption, false, $siteID);
				}
			}
			else
			{
				$error = "Y";
			}
			break;

		case "save_items_sort":
			if (isset($_POST["items"]))
			{
				foreach (array("show", "hide") as $status)
				{
					if (isset($_POST["items"][$status]) && is_array($_POST["items"][$status]))
					{
						$userOption[$status] = $_POST["items"][$status];
					}
					else
					{
						$userOption[$status] = array();
					}
				}

				CUserOptions::SetOption("intranet", $optionName, $userOption);

				if (isset($_POST["firstItemLink"]))
				{
					CUserOptions::SetOption("intranet", "left_menu_first_page_".$siteID, $_POST["firstItemLink"]);
				}
			}
			else
			{
				$error = "Y";
			}
			break;

		case "set_default_menu":
			CUserOptions::DeleteOption("intranet", "left_menu_first_page_".$siteID);
			CUserOptions::DeleteOption("intranet", "left_menu_self_items_".$siteID);
			CUserOptions::DeleteOption("intranet", "left_menu_standard_items_".$siteID);
			CUserOptions::DeleteOption("intranet", $optionName);

			if (COption::GetOptionString("intranet", "left_menu_preset", "", $siteID) == "custom")
			{
				CUserOptions::DeleteOptionsByName("intranet", "left_menu_preset_".$siteID);
			}
			break;

		case "collapse_menu":
			CUserOptions::SetOption("intranet", "left_menu_collapsed", "Y");
			break;
		case "expand_menu":
			CUserOptions::SetOption("intranet", "left_menu_collapsed", "N");
			break;

		case "set_preset":
			if (!isset($_POST["preset"]) || !in_array($_POST["preset"], array("social", "crm", "tasks", "sites")) || !isset($_POST["mode"]))
			{
				$error = GetMessage("LEFT_MENU_PRESET_ERROR");
			}
			else
			{
				if ($_POST["mode"] == "global" && $isAdmin)
				{
					COption::SetOptionString("intranet", "left_menu_preset", $_POST["preset"], false, $siteID);
				}
				else
				{
					CUserOptions::SetOption("intranet", "left_menu_preset_".$siteID, $_POST["preset"]);

					CUserOptions::DeleteOption("intranet", "left_menu_first_page_".$siteID);
				//	CUserOptions::DeleteOption("intranet", "left_menu_self_items_".$siteID);
				//	CUserOptions::DeleteOption("intranet", "left_menu_standard_items_".$siteID);
					CUserOptions::DeleteOption("intranet", $optionName);
				}

				$firstPageUrl = $_POST["siteDir"]."stream/";
				switch ($_POST["preset"])
				{
					case "tasks":
						$firstPageUrl = $_POST["siteDir"]."company/personal/user/".($_POST["mode"] == "global" ? "#USER_ID#" : $USER->GetID())."/tasks/";
						break;

					case "crm":
						if (CModule::IncludeModule("crm"))
						{
							$firstPageUrl = \Bitrix\Crm\Settings\EntityViewSettings::getDefaultPageUrl();
						}

						break;

					case "sites":
						$firstPageUrl = $_POST["siteDir"]."sites/";
						break;
				}

				if ($firstPageUrl)
				{
					if ($_POST["mode"] == "global" && $isAdmin)
					{
						COption::SetOptionString("intranet", "left_menu_first_page", $firstPageUrl, false, $siteID);
					}
					else
					{
						CUserOptions::SetOption("intranet", "left_menu_first_page_".$siteID, $firstPageUrl);
					}

					$arJsonData["url"] = str_replace("#USER_ID#", $USER->GetID(), $firstPageUrl);
				}

				if($_POST['mode'] === 'global' && IsModuleInstalled('bitrix24'))
				{
					$_SESSION['B24_SHOW_DEMO_LICENSE_HINT'] = 1;
				}
			}

			$showPresetPopup = COption::GetOptionString("intranet", "show_menu_preset_popup", "N") == "Y";
			if ($showPresetPopup)
			{
				COption::SetOptionString("intranet", "show_menu_preset_popup", "N");
			}

			break;
		case "delay_set_preset":
			$showPresetPopup = COption::GetOptionString("intranet", "show_menu_preset_popup", "N") == "Y";
			if ($showPresetPopup)
			{
				COption::SetOptionString("intranet", "show_menu_preset_popup", "N");
			}

			break;

		case "save_custom_preset":
			if (!$isAdmin)
				break;

			if (isset($_POST["userApply"]) && $_POST["userApply"] == "currentUser")
			{
				CUserOptions::DeleteOptionsByName("intranet", "left_menu_sorted_items_".$siteID);
				CUserOptions::DeleteOptionsByName("intranet", "left_menu_preset_".$siteID);
			}
			if (isset($_POST["itemsSort"]))
			{
				COption::SetOptionString("intranet", "left_menu_custom_preset_sort", serialize($_POST["itemsSort"]), false, $siteID);
			}
			if (isset($_POST["customItems"]))
			{
				COption::SetOptionString("intranet", "left_menu_custom_preset_items", serialize($_POST["customItems"]), false, $siteID);
			}

			COption::SetOptionString("intranet", "left_menu_preset", "custom", false, $siteID);
			if (isset($_POST["firstItemLink"]))
			{
				$firstPageUrl = $_POST["firstItemLink"];
				if (preg_match("~company/personal/user/\d+/tasks/$~i", $firstPageUrl, $match))
				{
					$firstPageUrl = $_POST["siteDir"]."company/personal/user/#USER_ID#/tasks/";
				}

				COption::SetOptionString("intranet", "left_menu_first_page", $firstPageUrl, false, $siteID);
			}

			break;

		case "set_group_filter":
			if (isset($_POST["filter"]) && in_array($_POST["filter"], array("all", "extranet", "favorites")))
			{
				CUserOptions::SetOption("intranet", "left_menu_group_filter_".$siteID, $_POST["filter"]);
			}
			break;

		case "add_to_favorites":
		case "remove_from_favorites":
			if (isset($_POST["groupId"]) && intval($_POST["groupId"]))
			{
				$setGroupToFavorites(
					intval($_POST["groupId"]),
					$_POST["action"] === "add_to_favorites" ? "Y" : "N"
				);
			}
			break;
		case "set_first_page":
			if (!isset($_POST["firstPageUrl"]))
			{
				break;
			}

			CUserOptions::SetOption("intranet", "left_menu_first_page_".$siteID, $_POST["firstPageUrl"]);
			break;
	}

	if (!empty($error))
		$arJsonData["error"] = $error;

	$APPLICATION->RestartBuffer();
	echo \Bitrix\Main\Web\Json::encode($arJsonData);
	\Bitrix\Main\Application::getInstance()->end();
}