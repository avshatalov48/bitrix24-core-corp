<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
final class CB24SearchTitle
{
	final public static function getUsers($searchString = false)
	{
		$result = array();

		$searchString = str_replace('%', '', $searchString)."%";

		$userNameTemplate = CSite::GetNameFormat(false);
		$userPageURLTemplate = \Bitrix\Main\Config\Option::get('socialnetwork', 'user_page', SITE_DIR.'company/personal/', SITE_ID).'user/#user_id#/';

		$userFilter = array(
			'ACTIVE' => 'Y'
		);

		$arExternalAuthId = array();
		if (\Bitrix\Main\ModuleManager::isModuleInstalled('replica'))
		{
			$arExternalAuthId[] = 'replica';
		}
		if (\Bitrix\Main\ModuleManager::isModuleInstalled('im'))
		{
			$arExternalAuthId[] = 'bot';
		}
		if (\Bitrix\Main\ModuleManager::isModuleInstalled('imconnector'))
		{
			$arExternalAuthId[] = 'imconnector';
		}
		if (\Bitrix\Main\ModuleManager::isModuleInstalled('mail'))
		{
			$arExternalAuthId[] = 'mail';
		}
		if (!empty($arExternalAuthId))
		{
			$userFilter['!=EXTERNAL_AUTH_ID'] = $arExternalAuthId;
		}

		if (\Bitrix\Main\ModuleManager::isModuleInstalled('intranet'))
		{
			$userFilter['!UF_DEPARTMENT'] = false;
		}

		if (!empty($searchString))
		{
			$userFilter[] = array(
				'LOGIC' => 'OR',
				'NAME' => $searchString,
				'LAST_NAME' => $searchString,
				'WORK_POSITION' => $searchString,
			);
		}

		$res = \Bitrix\Main\UserTable::getList(array(
			'filter' => array(
				'ACTIVE' => 'Y',
				$userFilter
			),
			'select' => array('ID', 'ACTIVE', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'PERSONAL_PHOTO', 'WORK_POSITION', 'PERSONAL_PROFESSION')
		));

		while($user = $res->fetch())
		{
			$image = CFile::ResizeImageGet(
				$user["PERSONAL_PHOTO"],
				array(
					"width" => 100,
					"height" => 100
				),
				BX_RESIZE_IMAGE_EXACT,
				false
			);

			$result[] = array(
				'ACTIVE' => $user['ACTIVE'],
				'NAME' => CUser::FormatName($userNameTemplate, array(
					'ID' => $user['ID'],
					'NAME' => $user['NAME'],
					'LAST_NAME' => $user['LAST_NAME'],
					'SECOND_NAME' => $user['SECOND_NAME'],
					'LOGIN' => $user['LOGIN'],
				)),
				'URL' => str_replace('#user_id#', $user['ID'], $userPageURLTemplate),
				'MODULE_ID' => '',
				'PARAM1' => '',
				'ID' => $user['ID'],
				'ITEM_ID' => 'U'.$user['ID'],
				'ICON' => empty($image['src'])? '': $image['src'],
				'TYPE' => 'users',
				'DESCRIPTION' => $user['WORK_POSITION'] ? $user['WORK_POSITION'] : ($user['PERSONAL_PROFESSION'] ? $user['PERSONAL_PROFESSION'] : '&nbsp;'),
				'IS_EXTRANET' => 'N',
				'IS_EMAIL' => 'N',
				'IS_CRM_EMAIL' => 'N'
			);
		}

		usort($result, array(__CLASS__, "resultCmp"));

		return $result;
	}

	final public static function getSonetGroups($searchString = false)
	{
		global $USER, $CACHE_MANAGER;

		$result = array();

		if (!$USER->isAuthorized())
		{
			return $result;
		}

		if (!\Bitrix\Main\Loader::includeModule('socialnetwork'))
		{
			return $result;
		}

		static $extranetIncluded = null;
		static $extranetSiteId = null;
		static $extranetUser = null;

		if ($extranetIncluded === null)
		{
			$extranetIncluded = \Bitrix\Main\Loader::includeModule('extranet');
			$extranetSiteId = ($extranetIncluded ? CExtranet::getExtranetSiteID() : false);
			$extranetUser = ($extranetIncluded ? !CExtranet::isIntranetUser() : false);
		}

		$groupPageURLTemplate = \Bitrix\Main\Config\Option::get('socialnetwork', 'workgroups_page', SITE_DIR.'workgroups/', SITE_ID).'group/#group_id#/';

		$groupFilter = array();

		if (!empty($searchString))
		{
			$groupFilter['%NAME'] = $searchString;
		}

		if ($extranetUser)
		{
			$userGroupList = array();
			$res = \Bitrix\Socialnetwork\UserToGroupTable::getList(array(
				'filter' => array(
					'USER_ID' => $USER->getId(),
					'@ROLE' => \Bitrix\Socialnetwork\UserToGroupTable::getRolesMember()
				),
				'select' => array('GROUP_ID')
			));

			while($relation = $res->fetch())
			{
				$userGroupList[] = intval($relation['GROUP_ID']);
			}

			if (empty($userGroupList))
			{
				return $result;
			}
			$groupFilter['@ID'] = $userGroupList;
		}
		elseif (!CSocNetUser::IsCurrentUserModuleAdmin(SITE_ID, false))
		{
			$groupFilter['CHECK_PERMISSIONS'] = $USER->GetId();
		}

		$cacheResult = $obCache = false;

		if (empty($searchString))
		{
			$cacheTtl = 3153600;
			$cacheId = 'search_title_sonetgroups_'.md5(serialize($groupFilter).$extranetSiteId.$groupPageURLTemplate);
			$cacheDir = '/intranet/search/sonetgroups/';

			$obCache = new CPHPCache;
			if($obCache->InitCache($cacheTtl, $cacheId, $cacheDir))
			{
				$cacheResult = $result = $obCache->GetVars();
			}
		}

		if ($cacheResult === false)
		{
			if ($obCache)
			{
				$obCache->StartDataCache();
				if(defined("BX_COMP_MANAGED_CACHE"))
				{
					$CACHE_MANAGER->StartTagCache($cacheDir);
				}
			}

			$res = CSocnetGroup::getList(
				array('NAME' => 'ASC'),
				$groupFilter,
				false,
				false,
				array("ID", "NAME", "IMAGE_ID", "DESCRIPTION")
			);

			$groupList = $groupIdList = array();
			while($group = $res->fetch())
			{
				$groupIdList[] = $group["ID"];
				$groupList[$group["ID"]] = $group;
			}

			$memberGroupIdList = array();

			if ($extranetUser)
			{
				$memberGroupIdList = $groupIdList;
			}
			elseif (!empty($groupIdList))
			{
				$res = \Bitrix\Socialnetwork\UserToGroupTable::getList(array(
					'filter' => array(
						'USER_ID' => $USER->getId(),
						'@GROUP_ID' => $groupIdList,
						'@ROLE' => \Bitrix\Socialnetwork\UserToGroupTable::getRolesMember()
					),
					'select' => array('GROUP_ID')
				));
				while($relation = $res->fetch())
				{
					$memberGroupIdList[] = $relation['GROUP_ID'];
				}
			}

			foreach($groupList as $group)
			{
				$image = CFile::ResizeImageGet(
					$group["IMAGE_ID"],
					array(
						"width" => 100,
						"height" => 100
					),
					BX_RESIZE_IMAGE_EXACT,
					false
				);

				$site = '';
				$isExtranet = false;
				$rsGroupSite = CSocNetGroup::GetSite($group["ID"]);
				while ($arGroupSite = $rsGroupSite->fetch())
				{
					if (
						empty($site)
						&& (
							!$extranetSiteId
							|| $arGroupSite["LID"] != $extranetSiteId
						)
					)
					{
						$site = $arGroupSite["LID"];
					}
					else
					{
						$isExtranet = true;
					}
				}

				$result[] = array(
					'ID' => $group['ID'],
					'NAME' => $group['NAME'],
					'URL' => str_replace('#group_id#', $group['ID'], $groupPageURLTemplate),
					'MODULE_ID' => '',
					'PARAM1' => '',
					'ITEM_ID' => 'G'.$group['ID'],
					'ICON' => empty($image['src'])? '': $image['src'],
					'TYPE' => 'sonetgroups',
					'IS_EXTRANET' => $isExtranet,
					'SITE' => $site,
					'IS_MEMBER' => in_array($group['ID'], $memberGroupIdList)
				);
			}

			if ($obCache)
			{
				if (defined("BX_COMP_MANAGED_CACHE"))
				{
					$CACHE_MANAGER->RegisterTag("sonet_group");
					$CACHE_MANAGER->RegisterTag("sonet_user2group_U".$USER->getID());
					$CACHE_MANAGER->EndTagCache();
				}

				$obCache->EndDataCache($result);
			}
		}

		return $result;
	}

	final public static function convertAjaxToClientDb($arEntity, $entityType)
	{
		static $timestamp = false;

		if (!$timestamp)
		{
			$timestamp = time();
		}

		$result = array();
		if ($entityType == 'sonetgroups')
		{
			$result = array(
				'id' => 'G'.$arEntity["ID"],
				'entityId' => $arEntity["ID"],
				'name' => $arEntity["NAME"],
				'avatar' => empty($arEntity['ICON'])? '': $arEntity['ICON'],
				'desc' => empty($arEntity['DESCRIPTION'])? '': (TruncateText($arEntity['DESCRIPTION'], 100)),
				'isExtranet' => ($arEntity['IS_EXTRANET'] ? "Y" : "N"),
				'site' => $arEntity['SITE'],
				'isMember' => (isset($arEntity['IS_MEMBER']) && $arEntity['IS_MEMBER'] ? "Y" : "N")
			);
			$result['checksum'] = md5(serialize($result));
			$result['timestamp'] = $timestamp;
		}
		elseif($entityType == 'menuitems')
		{
			$result = array(
				'id' => 'M'.$arEntity["URL"],
				'entityId' => $arEntity["URL"],
				'name' => $arEntity["NAME"],
			);
			$result['checksum'] = md5(serialize($result));
			$result['timestamp'] = $timestamp;
		}
		elseif($entityType == 'users')
		{
			$result = array(
				'id' => 'U'.$arEntity["ID"],
				'entityId' => $arEntity["ID"],
				'name' => $arEntity["NAME"],
				'avatar' => empty($arEntity['ICON'])? '': $arEntity['ICON'],
				'desc' => empty($arEntity['DESCRIPTION'])? '': $arEntity['DESCRIPTION'],
				'isExtranet' => 'N',
				'isEmail' => 'N',
				'active' => 'Y'
			);
			$result['checksum'] = md5(serialize($result));
			$result['login'] = '';
		}

		return $result;
	}

	final public static function getMenuItems($searchString = false)
	{
		global $APPLICATION;

		$result = array();

		$arMenuResult = $APPLICATION->IncludeComponent(
			"bitrix:menu",
			"left_vertical",
			array(
				"ROOT_MENU_TYPE" => isModuleInstalled("bitrix24") ? "superleft" : "top",
				"MENU_CACHE_TYPE" => "Y",
				"MENU_CACHE_TIME" => "604800",
				"MENU_CACHE_USE_GROUPS" => "N",
				"MENU_CACHE_USE_USERS" => "Y",
				"CACHE_SELECTED_ITEMS" => "N",
				"MENU_CACHE_GET_VARS" => array(),
				"MAX_LEVEL" => "2",
				"CHILD_MENU_TYPE" => "left", // may be 'top' for b24
				"USE_EXT" => "Y",
				"DELAY" => "N",
				"ALLOW_MULTI_SELECT" => "N",
				"RETURN" => "Y"
			),
			false
		);

		foreach($arMenuResult as $menuItem)
		{
			if (empty($menuItem['LINK']))
				continue;

			if (
				empty($searchString)
				|| strpos(ToLower($menuItem['TEXT']), ToLower($searchString)) !== false
			)
			{
				$result[] = array(
					'NAME' => $menuItem['TEXT'],
					'URL' =>
						isset($menuItem['PARAMS']) && isset($menuItem['PARAMS']["real_link"]) ?
							$menuItem['PARAMS']["real_link"] :
							$menuItem['LINK'],
					'MODULE_ID' => '',
					'PARAM1' => '',
					'ITEM_ID' => 'M'.$menuItem['LINK'],
					'ICON' => ''
				);
			}
		}

		usort($result, array(__CLASS__, "resultCmp"));

		return $result;
	}

	final public static function customSearch($searchString, $arParams, &$arResult)
	{
		static $bSocialnetworkIncluded = null;
		static $bExtranetSite = null;

		if ($bSocialnetworkIncluded === null)
		{
			$bSocialnetworkIncluded = \Bitrix\Main\Loader::includeModule('socialnetwork');
		}

		if ($bExtranetSite === null)
		{
			$bExtranetSite = (\Bitrix\Main\Loader::includeModule('extranet') && CExtranet::IsExtranetSite());
		}

		for($i = 0; $i < $arParams["NUM_CATEGORIES"]; $i++)
		{
			$categoryCode = $arParams["CATEGORY_".$i];

			if (is_array($categoryCode))
			{
				$categoryCode = $categoryCode[0];
			}

			if (strpos($categoryCode, 'custom_') === 0)
			{
				$categoryTitle = trim($arParams["CATEGORY_".$i."_TITLE"]);
				if(empty($categoryTitle))
					continue;

				$arResult["CATEGORIES"][$i] = array(
					"TITLE" => htmlspecialcharsbx($categoryTitle),
					"ITEMS" => array()
				);

				if (
					$categoryCode == 'custom_users'
					&& !$bExtranetSite
				)
				{
					$arResult["customUsersCategoryId"] = $i;
					$arResult["CATEGORIES"][$i]["ITEMS"] = CB24SearchTitle::getUsers($searchString);

					if ($arResult["customResultEmpty"] && !empty($arResult["CATEGORIES"][$i]["ITEMS"]))
					{
						$arResult["customResultEmpty"] = false;
					}

					foreach($arResult["CATEGORIES"][$i]["ITEMS"] as $key => $arItem)
					{
						$clientDbItem = CB24SearchTitle::convertAjaxToClientDb($arItem, 'users');
						$arResult["CATEGORIES"][$i]["ITEMS"][$key]['CHECKSUM'] = $clientDbItem['checksum'];
					}
				}
				elseif (
					$categoryCode == 'custom_sonetgroups'
					&& $bSocialnetworkIncluded
				)
				{
					$arResult["customSonetGroupsCategoryId"] = $i;
					$arResult["CATEGORIES"][$i]["ITEMS"] = CB24SearchTitle::getSonetGroups($searchString);

					if ($arResult["customResultEmpty"] && !empty($arResult["CATEGORIES"][$i]["ITEMS"]))
					{
						$arResult["customResultEmpty"] = false;
					}

					foreach($arResult["CATEGORIES"][$i]["ITEMS"] as $key => $arItem)
					{
						$clientDbItem = CB24SearchTitle::convertAjaxToClientDb($arItem, 'sonetgroups');
						$arResult["CATEGORIES"][$i]["ITEMS"][$key]['CHECKSUM'] = $clientDbItem['checksum'];
					}
				}
				elseif ($categoryCode == 'custom_menuitems')
				{
					$arResult["CATEGORIES"][$i]["ITEMS"] = CB24SearchTitle::getMenuItems($searchString);

					if ($arResult["customResultEmpty"] && !empty($arResult["CATEGORIES"][$i]["ITEMS"]))
					{
						$arResult["customResultEmpty"] = false;
					}

					foreach($arResult["CATEGORIES"][$i]["ITEMS"] as $key => $arItem)
					{
						$clientDbItem = CB24SearchTitle::convertAjaxToClientDb($arItem, 'menuitems');
						$arResult["CATEGORIES"][$i]["ITEMS"][$key]['CHECKSUM'] = $clientDbItem['checksum'];
					}
				}
			}
		}
	}

	final public static function resultCmp($a, $b)
	{
		if ($a['NAME'] == $b['NAME'])
		{
			return 0;
		}
		return ($a['NAME'] < $b['NAME']) ? -1 : 1;
	}
}
?>