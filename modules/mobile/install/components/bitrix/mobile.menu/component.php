<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var $USER CUser
 */
$arResult = Array();
$USER_ID = $USER->GetID();

$arResult = array();
$ttl = (defined("BX_COMP_MANAGED_CACHE") ? 2592000 : 600);
$extEnabled = IsModuleInstalled('extranet');

$cache_id = 'user_mobile_menu_' . $USER_ID . '_' . $extEnabled . '_' . LANGUAGE_ID.'_'.CSite::GetNameFormat(false);
$cache_dir = '/bx/user_mobile_menu/user_'. $USER_ID;
$obCache = new CPHPCache;
if ($obCache->InitCache($ttl, $cache_id, $cache_dir))
{
	$arResult = $obCache->GetVars();
}
else
{
	global $CACHE_MANAGER;
	$CACHE_MANAGER->StartTagCache($cache_dir);
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

	$arSGGroup = array();
	$arExtSGGroup = array();
	$arExtSGGroupTmp = array();



	if (CModule::IncludeModule("socialnetwork"))
	{
		$strGroupSubjectLinkTemplate = SITE_DIR . "mobile/log/?group_id=#group_id#";
		$extGroupID = array();
		$arGroupFilterMy = array(
			"USER_ID" => $USER_ID,
			"<=ROLE" => SONET_ROLES_USER,
			"GROUP_ACTIVE" => "Y",
			"!GROUP_CLOSED" => "Y",
		);

		// Extranet group
		if (CModule::IncludeModule("extranet") && !CExtranet::IsExtranetSite())
		{
			$arGroupFilterMy["GROUP_SITE_ID"] = CExtranet::GetExtranetSiteID();
			$dbGroups = CSocNetUserToGroup::GetList(
				array("GROUP_NAME" => "ASC"),
				$arGroupFilterMy,
				false,
				false,
				array('ID', 'GROUP_ID', 'GROUP_NAME', 'GROUP_SITE_ID')
			);

			while ($arGroups = $dbGroups->GetNext())
			{
				$arExtSGGroupTmp[$arGroups["GROUP_ID"]] = array(
					$arGroups["GROUP_NAME"],
					str_replace("#group_id#", $arGroups["GROUP_ID"], $strGroupSubjectLinkTemplate),
					array(),
					array("counter_id" => "SG" . $arGroups["GROUP_ID"]),
					""
				);

				$extGroupID[] = $arGroups["GROUP_ID"];
			}
		}

		$arGroupIDCurrentSite = array();

		// Socialnetwork
		$arGroupFilterMy["GROUP_SITE_ID"] = SITE_ID;
		$dbGroups = CSocNetUserToGroup::GetList(
			array("GROUP_NAME" => "ASC"),
			$arGroupFilterMy,
			false,
			false,
			array('ID', 'GROUP_ID', 'GROUP_NAME', 'GROUP_SITE_ID')
		);

		while ($arGroups = $dbGroups->GetNext())
		{
			$arGroupIDCurrentSite[] = $arGroups['GROUP_ID'];

			if (in_array($arGroups['GROUP_ID'], $extGroupID))
			{
				continue;
			}

			$arSGGroup[] = array(
				$arGroups["GROUP_NAME"],
				str_replace("#group_id#", $arGroups["GROUP_ID"], $strGroupSubjectLinkTemplate),
				array(),
				array("counter_id" => "SG" . $arGroups["GROUP_ID"]),
				""
			);
		}

		foreach ($arExtSGGroupTmp as $groupID => $arGroupItem)
		{
			if (in_array($groupID, $arGroupIDCurrentSite))
			{
				$arExtSGGroup[] = $arGroupItem;
			}
		}
	}

	$arMenuApps = array();
	if (CModule::IncludeModule("rest"))
	{
		$arUserGroupCode = $USER->GetAccessCodes();
		$numLocalApps = 0;

		$dbApps = \Bitrix\Rest\AppTable::getList(array(
			'order' => array("ID" => "ASC"),
			'filter' => array(
				"=ACTIVE" => \Bitrix\Rest\AppTable::ACTIVE,
				"=MOBILE" => \Bitrix\Rest\AppTable::ACTIVE
			),
			'select' => array(
				'ID', 'STATUS', 'ACCESS', 'MENU_NAME' => 'LANG.MENU_NAME', 'MENU_NAME_DEFAULT' => 'LANG_DEFAULT.MENU_NAME', 'MENU_NAME_LICENSE' => 'LANG_LICENSE.MENU_NAME'
			)
		));
		
		while ($arApp = $dbApps->fetch())
		{
			if ($arApp["STATUS"] == \Bitrix\Rest\AppTable::STATUS_LOCAL)
			{
				$numLocalApps++;
			}

			$lang = in_array(LANGUAGE_ID, array("ru", "en", "de")) ? LANGUAGE_ID : LangSubst(LANGUAGE_ID);
			if (strlen($arApp["MENU_NAME"]) > 0 || strlen($arApp['MENU_NAME_DEFAULT']) > 0 || strlen($arApp['MENU_NAME_LICENSE']) > 0)
			{
				$appRightAvailable = false;
				if (\CRestUtil::isAdmin())
				{
					$appRightAvailable = true;
				}
				elseif (!empty($arApp["ACCESS"]))
				{
					$rights = explode(",", $arApp["ACCESS"]);
					foreach ($rights as $rightID)
					{
						if (in_array($rightID, $arUserGroupCode))
						{
							$appRightAvailable = true;
							break;
						}
					}
				}
				else
				{
					$appRightAvailable = true;
				}

				if ($appRightAvailable)
				{
					$appName = $arApp["MENU_NAME"];

					if (strlen($appName) <= 0)
					{
						$appName = $arApp['MENU_NAME_DEFAULT'];
					}
					if (strlen($appName) <= 0)
					{
						$appName = $arApp['MENU_NAME_LICENSE'];
					}

					$arMenuApps[] = Array(
						"name" => htmlspecialcharsbx($appName),
						"id" => $arApp["ID"],
						"url" => "/mobile/marketplace/?id=" . $arApp["ID"],
					);
				}
			}
		}
	}


	$CACHE_MANAGER->RegisterTag('sonet_group');
	$CACHE_MANAGER->RegisterTag('USER_CARD_' . intval($USER_ID / TAGGED_user_card_size));
	$CACHE_MANAGER->RegisterTag('sonet_user2group_U' . $USER_ID);
	$CACHE_MANAGER->EndTagCache();

	$arResult["GROUP_MENU"] = $arSGGroup;
	$arResult["EXTRANET_MENU"] = $arExtSGGroup;
	$arResult["MARKETPLACE_MENU"] = $arMenuApps;


	if ($obCache->StartDataCache())
	{
		$obCache->EndDataCache($arResult);
		unset($arSGGroup, $arExtSGGroup);
	}
}

if ($arResult["USER"]["AVATAR"])
{
	$file = CHTTP::urnEncode($arResult["USER"]["AVATAR"]["src"], "UTF-8");
	\Bitrix\Main\Data\AppCacheManifest::getInstance()->addFile($file);
}


unset($obCache);
$this->IncludeComponentTemplate();
?>