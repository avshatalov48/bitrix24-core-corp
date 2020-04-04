<?
define("NOT_CHECK_PERMISSIONS", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if ($_SERVER["REQUEST_METHOD"]=="POST" && strlen($_POST["action"])>0 && check_bitrix_sessid())
{
	if (Bitrix\Main\Loader::includeModule("intranet"))
	{
		Bitrix\Intranet\Composite\CacheProvider::deleteUserCache();
	}
	
	if (isset($_POST["site_id"]) && trim($_POST["site_id"]))
	{
		$siteID = trim($_POST["site_id"]);
	}
	else
	{
		$dbSite = CSite::GetList($by="sort", $order="desc", array("DEFAULT"=>"Y"));
		if ($arSite = $dbSite->Fetch())
		{
			$siteID = $arSite["LID"];
		}
	}

	$optionName = "user_menu_items_".$siteID;
	$optionNameUserFavoriteItems = "user_added_favorite_items_".$siteID;
	$optionSectionsName = "user_menu_sections_".$siteID;
	$error = "";

	$menuItemID = trim($_POST["menu_item_id"]);
	if (isset($_POST["title_item_id"]))
	{
		$titleItemID = trim($_POST["title_item_id"]);
	}
	$userOption = CUserOptions::GetOption("bitrix24", $optionName);

	$arJsonData = array();

	switch ($_POST["action"])
	{
		case "get_app_rigths":
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
		case "add_favorite":
			if (
				!isset($userOption["menu-favorites"])
				|| !isset($userOption["menu-favorites"]["show"])
				|| isset($userOption["menu-favorites"]["show"]) && !in_array($menuItemID, $userOption["menu-favorites"]["show"])
			)
			{
				if (isset($_POST["all_show_items"]))
				{
					$userOption["menu-favorites"]["show"] = array();
					$allShowItems = $_POST["all_show_items"];
					foreach($allShowItems as $itemId)
						$userOption["menu-favorites"]["show"][] = $itemId;
				}
				$userOption["menu-favorites"]["show"][] = $menuItemID;
				CUserOptions::SetOption("bitrix24", $optionName, $userOption);

				$favoriteItemsUserAdded = CUserOptions::GetOption("bitrix24", $optionNameUserFavoriteItems);
				if (is_array($favoriteItemsUserAdded) && !in_array($menuItemID, $favoriteItemsUserAdded) || !is_array($favoriteItemsUserAdded))
					$favoriteItemsUserAdded[] = $menuItemID;
				CUserOptions::SetOption("bitrix24", $optionNameUserFavoriteItems, $favoriteItemsUserAdded);
			}
			break;
		case "delete_favorite":
			$arStatus = array("show", "hide");
			foreach ($arStatus as $status)
			{
				if (isset($userOption["menu-favorites"][$status]) && in_array($menuItemID, $userOption["menu-favorites"][$status]))
				{
					$key = array_search($menuItemID, $userOption["menu-favorites"][$status]);
					unset($userOption["menu-favorites"][$status][$key]);
					if (empty($userOption["menu-favorites"][$status]))
						unset($userOption["menu-favorites"][$status]);
					if (empty($userOption["menu-favorites"]))
						unset($userOption["menu-favorites"]);
					if (empty($userOption))
						CUserOptions::DeleteOption("bitrix24", $optionName);
					else
						CUserOptions::SetOption("bitrix24", $optionName, $userOption);
					break;
				}
			}
			$favoriteItemsUserAdded = CUserOptions::GetOption("bitrix24", $optionNameUserFavoriteItems);
			if (is_array($favoriteItemsUserAdded) && in_array($menuItemID, $favoriteItemsUserAdded))
			{
				$key = array_search($menuItemID, $favoriteItemsUserAdded);
				unset($favoriteItemsUserAdded[$key]);
				CUserOptions::SetOption("bitrix24", $optionNameUserFavoriteItems, $favoriteItemsUserAdded);
			}
			break;
		case "add_favorite_admin":
			if (
				IsModuleInstalled("bitrix24") && $GLOBALS['USER']->CanDoOperation('bitrix24_config')
				|| !IsModuleInstalled("bitrix24") && $GLOBALS['USER']->IsAdmin()
			)
			{
				$adminOption = COption::GetOptionString("bitrix24", "admin_menu_items");
				if ($adminOption)
					$adminOption = unserialize($adminOption);
				else
					$adminOption = array();
				if (is_array($adminOption) && !in_array($menuItemID, $adminOption))
				{
					$adminOption[] = $menuItemID;
					$adminOption = serialize($adminOption);
					COption::SetOptionString("bitrix24", "admin_menu_items", $adminOption, false, SITE_ID);
				}
			}
			else
			{
				$error = "Y";
			}
			break;
		case "delete_favorite_admin":
			if (
				IsModuleInstalled("bitrix24") && $GLOBALS['USER']->CanDoOperation('bitrix24_config')
				|| !IsModuleInstalled("bitrix24") && $GLOBALS['USER']->IsAdmin()
			)
			{
				$adminOption = COption::GetOptionString("bitrix24", "admin_menu_items");
				if ($adminOption)
					$adminOption = unserialize($adminOption);
				else
					$adminOption = array();
				if (is_array($adminOption) && in_array($menuItemID, $adminOption))
				{
					$key = array_search($menuItemID, $adminOption);
					unset($adminOption[$key]);
					$adminOption = serialize($adminOption);
					COption::SetOptionString("bitrix24", "admin_menu_items", $adminOption, false, SITE_ID);
				}
			}
			else
			{
				$error = "Y";
			}
			break;
		case "hide":
			if (
				!isset($userOption[$titleItemID])
				|| !isset($userOption[$titleItemID]["hide"])
				|| isset($userOption[$titleItemID]["hide"]) && !in_array($menuItemID, $userOption[$titleItemID]["hide"])
			)
			{
				$userOption[$titleItemID]["hide"][] = $menuItemID;
				if ($titleItemID == "menu-favorites" && !isset($userOption[$titleItemID]["show"]) && isset($_POST["all_show_items"]))
				{
					$userOption[$titleItemID]["show"] = $_POST["all_show_items"];
				}
				if (isset($userOption[$titleItemID]["show"]) && in_array($menuItemID, $userOption[$titleItemID]["show"]))
				{
					$key = array_search($menuItemID, $userOption[$titleItemID]["show"]);
					unset($userOption[$titleItemID]["show"][$key]);
				}
				CUserOptions::SetOption("bitrix24", $optionName, $userOption);
			}
			break;
		case "show":
			if (in_array($menuItemID, $userOption[$titleItemID]["hide"]))
			{
				if ($titleItemID == "menu-favorites" && !in_array($menuItemID, $userOption[$titleItemID]["show"]))
					$userOption[$titleItemID]["show"][] = $menuItemID;
				$key = array_search($menuItemID, $userOption[$titleItemID]["hide"]);
				unset($userOption[$titleItemID]["hide"][$key]);
				if (empty($userOption[$titleItemID]["hide"]))
					unset($userOption[$titleItemID]["hide"]);
				if (empty($userOption[$titleItemID]))
					unset($userOption[$titleItemID]);
				if (empty($userOption))
					CUserOptions::DeleteOption("bitrix24", $optionName);
				else
					CUserOptions::SetOption("bitrix24", $optionName, $userOption);
			}
			break;
		case "sort_items":
			if (isset($_POST["all_title_items"]))
			{
				if (!isset($userOption[$titleItemID]["show"]))
				{
					$oldSortItems = $userOption[$titleItemID]["show"];
					$userOption[$titleItemID]["show"] = array();

					$userOption[$titleItemID]["show"] = $_POST["all_title_items"];

					$lostItems = array();
					if (is_array($oldSortItems))
					{
						foreach ($oldSortItems as $itemID)
						{
							if (!in_array($itemID, $_POST["all_title_items"]))
								$lostItems[] = $itemID;
						}
					}
					if (!empty($lostItems))
						$userOption[$titleItemID]["show"] = array_merge($userOption[$titleItemID]["show"], $lostItems);
				}
				else
					$userOption[$titleItemID]["show"] = $_POST["all_title_items"];
				CUserOptions::SetOption("bitrix24", $optionName, $userOption);
			}
			else
			{
				$error = "Y";
			}
			break;
		case "sort_sections":
			if (isset($_POST["all_sections"]) && is_array($_POST["all_sections"]))
			{
				CUserOptions::SetOption("bitrix24", $optionSectionsName, $_POST["all_sections"]);
			}
			else
			{
				$error = "Y";
			}
			break;
	}

	if(defined("BX_COMP_MANAGED_CACHE"))
	{
		global $CACHE_MANAGER;
		$CACHE_MANAGER->ClearByTag('sonet_group');
	}

	if (!empty($error))
		$arJsonData["error"] = "Y";

	$APPLICATION->RestartBuffer();
	echo \Bitrix\Main\Web\Json::encode($arJsonData);
	die();
}
?>

