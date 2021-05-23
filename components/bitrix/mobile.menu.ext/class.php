<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * Class MobileMenu
 */
class MobileMenu extends \CBitrixComponent
{
	public function onPrepareComponentParams($arParams)
	{
		return $arParams;
	}

	public function executeComponent()
	{
		global $USER;

		/**
		 * @var CAllUser $USER
		 */
		$USER_ID = $USER->GetID();
		$arResult = Array();
		$this->arResult =& $arResult;

		$arResult = array();
		$ttl = (defined("BX_COMP_MANAGED_CACHE") ? 2592000 : 600);
		$extEnabled = IsModuleInstalled('extranet');

		$cache_id = 'user_mobile_menu_' . $USER_ID . '_' . $extEnabled . '_' . LANGUAGE_ID . '_' . CSite::GetNameFormat(false);
		$cache_dir = '/bx/mobile_menu/user_' . $USER_ID;
		$obCache = new CPHPCache;

		if ($obCache->InitCache($ttl, $cache_id, $cache_dir))
		{
			$arResult = $obCache->GetVars();
		}
		else
		{
			global $CACHE_MANAGER;
			$CACHE_MANAGER->StartTagCache($cache_dir);
			$arResult["MENU"] = include(\Bitrix\Main\Application::getDocumentRoot() . $this->getPath() . "/.mobile_menu.php");
			$host = Bitrix\Main\Context::getCurrent()->getServer()->getHttpHost();
			$host = preg_replace("/:(80|443)$/", "", $host);
			$arResult["HOST"] = htmlspecialcharsbx($host);
			$arResult["USER"] = $USER->GetByID($USER_ID)->GetNext();
			$arResult["USER_FULL_NAME"] = $arResult["USER"]["FULL_NAME"] = CUser::FormatName(CSite::GetNameFormat(false), array(
				"NAME" => $USER->GetFirstName(),
				"LAST_NAME" => $USER->GetLastName(),
				"SECOND_NAME" => $USER->GetSecondName(),
				"LOGIN" => $USER->GetLogin()
			));

			$arResult["USER"]["AVATAR"] = false;

			if ($arResult["USER"]["PERSONAL_PHOTO"])
			{
				$imageFile = CFile::GetFileArray($arResult["USER"]["PERSONAL_PHOTO"]);
				if ($imageFile !== false)
				{
					$arResult["USER"]["AVATAR"] = CFile::ResizeImageGet($imageFile, array("width" => 1200, "height" => 1020), BX_RESIZE_IMAGE_EXACT, false, false, false, 50);
				}
			}

			$CACHE_MANAGER->RegisterTag('sonet_group');
			$CACHE_MANAGER->RegisterTag('USER_CARD_' . intval($USER_ID / TAGGED_user_card_size));
			$CACHE_MANAGER->RegisterTag('sonet_user2group_U' . $USER_ID);
			$CACHE_MANAGER->RegisterTag('mobile_custom_menu');
			$CACHE_MANAGER->EndTagCache();

			if ($obCache->StartDataCache())
			{
				$obCache->EndDataCache($arResult);
			}
		}

		$events = \Bitrix\Main\EventManager::getInstance()->findEventHandlers("mobile", "onMobileMenuStructureBuilt");
		if (count($events) > 0)
		{
			$menu = ExecuteModuleEventEx($events[0], array($arResult["MENU"]));
			$arResult["MENU"] = $menu;
		}

		usort($arResult["MENU"], 'MobileMenu::sort');

		if ($arResult["USER"]["AVATAR"])
		{
			$file = CHTTP::urnEncode($arResult["USER"]["AVATAR"]["src"], "UTF-8");
			\Bitrix\Main\Data\AppCacheManifest::getInstance()->addFile($file);
		}


		unset($obCache);

		$this->IncludeComponentTemplate();
	}

	static function sort($item, $anotherItem)
	{
		$itemSort = (array_key_exists("sort", $item) ? $item["sort"] : 100);
		$anotherSort = (array_key_exists("sort", $anotherItem) ? $anotherItem["sort"] : 100);
		if ($itemSort > $anotherSort)
		{
			return 1;
		}

		if ($itemSort == $anotherSort)
		{
			return 0;
		}

		return -1;
	}

	public function getMenuItemAttributes($itemDescription)
	{

	}


}