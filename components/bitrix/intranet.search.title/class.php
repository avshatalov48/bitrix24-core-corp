<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Settings\DealSettings;
use Bitrix\Crm\Settings\LeadSettings;
use Bitrix\Crm\Settings\InvoiceSettings;
use Bitrix\Crm\Settings\QuoteSettings;
use Bitrix\Crm\Settings\EntityViewSettings;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\PhoneNumber;
use Bitrix\Main\Text\Emoji;
use Bitrix\Socialnetwork\UserToGroupTable;

class CIntranetSearchTitleComponent extends CBitrixComponent
{
	private function getUsers($searchString = false): array
	{
		global $USER;

		$result = [];

		if (!Loader::includeModule('socialnetwork'))
		{
			return $result;
		}

		$searchStringOriginal = $searchString;
//		$searchString = str_replace('%', '', $searchString)."%";

		$userNameTemplate = \CSite::GetNameFormat(false);
		$userPageURLTemplate = Option::get('socialnetwork', 'user_page', SITE_DIR.'company/personal/', SITE_ID).'user/#user_id#/';

		$userFilter = array(
			'ACTIVE' => 'Y',
			'!=EXTERNAL_AUTH_ID' => \Bitrix\Main\UserTable::getExternalUserTypes()
		);

		$searchByEmail = false;
		if (!empty($searchString))
		{
			$matchesPhones = [];
			$phoneParserManager = PhoneNumber\Parser::getInstance();
			preg_match_all('/'.$phoneParserManager->getValidNumberPattern().'/i', $searchString, $matchesPhones);

			if (
				!empty($matchesPhones)
				&& !empty($matchesPhones[0])
			)
			{
				foreach($matchesPhones[0] as $phone)
				{
					$convertedPhone = PhoneNumber\Parser::getInstance()
						->parse($phone)
						->format(PhoneNumber\Format::E164);
					$searchString = str_replace($phone, $convertedPhone, $searchString);
				}
			}

			$findFilter = \Bitrix\Main\UserUtils::getAdminSearchFilter([
				'FIND' => $searchString
			]);
			if (!empty($findFilter))
			{
				$userFilter = array_merge($userFilter, $findFilter);
			}
			/*
						$searchStringList = preg_split('/\s+/', trim(ToUpper($searchString)));
						array_walk(
							$searchStringList,
							function (&$val, $key)
							{
								$val = str_replace('%', '', $val) . '%';
							}
						);

						if (count($searchStringList) == 2)
						{
							$userFilter[] = array(
								'LOGIC' => 'OR',
								array('LOGIC' => 'AND', 'NAME' => $searchStringList[0], 'LAST_NAME' => $searchStringList[1]),
								array('LOGIC' => 'AND', 'NAME' => $searchStringList[1], 'LAST_NAME' => $searchStringList[0]),
							);
						}
						else
						{
							$subFilter = [
								'LOGIC' => 'OR',
								'NAME' => $searchString,
								'LAST_NAME' => $searchString,
								'WORK_POSITION' => $searchString,
							];

							if (check_email($searchStringOriginal, true))
							{
								$searchByEmail = true;
								$subFilter['=EMAIL_OK'] = 1;
							}

							$userFilter[] = $subFilter;
						}
			*/
		}


		if (!\CSocNetUser::isCurrentUserModuleAdmin())
		{
			$extranetInstalled = Loader::includeModule('extranet');
			$myGroupUsersList = ($extranetInstalled ? \CExtranet::getMyGroupsUsersSimple(\CExtranet::getExtranetSiteId()) : []);

			if (!empty($myGroupUsersList))
			{
				if (!\CExtranet::isIntranetUser())
				{
					$userFilter['@ID'] = $myGroupUsersList;
				}
				else
				{
					$userFilter[] = array(
						'LOGIC' => 'OR',
						'!UF_DEPARTMENT' => false,
						'@ID' => $myGroupUsersList
					);
				}
			}
			elseif ($extranetInstalled && !\CExtranet::isIntranetUser())
			{
				$userFilter['=ID'] = $USER->getId();
			}
			else
			{
				$userFilter['!UF_DEPARTMENT'] = false;
			}
		}

		$selectFields = [
			'ID', 'ACTIVE', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'PERSONAL_PHOTO', 'WORK_POSITION', 'PERSONAL_PROFESSION',
		];

		/*
				if ($searchByEmail)
				{
					$selectFields[] = new \Bitrix\Main\Entity\ExpressionField('EMAIL_OK', 'CASE WHEN UPPER(%s) = "'.$DB->ForSql(mb_strtoupper(str_replace('%', '%%', $searchStringOriginal))).'" THEN 1 ELSE 0 END', 'EMAIL');
				}
		*/

		$res = \Bitrix\Main\UserTable::getList(array(
			'filter' => $userFilter,
			'select' => $selectFields
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
				'NAME' => \CUser::formatName(
					$userNameTemplate,
					array(
						'ID' => $user['ID'],
						'NAME' => $user['NAME'],
						'LAST_NAME' => $user['LAST_NAME'],
						'SECOND_NAME' => $user['SECOND_NAME'],
						'LOGIN' => $user['LOGIN'],
					),
					true
				),
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

	private function getSonetGroups($searchString = false): array
	{
		global $USER, $CACHE_MANAGER;

		$result = [];

		if (!$USER->isAuthorized())
		{
			return $result;
		}

		if (!Loader::includeModule('socialnetwork'))
		{
			return $result;
		}

		static $extranetIncluded = null;
		static $extranetSiteId = null;
		static $extranetUser = null;

		if ($extranetIncluded === null)
		{
			$extranetIncluded = Loader::includeModule('extranet');
			$extranetSiteId = ($extranetIncluded ? CExtranet::getExtranetSiteID() : false);
			$extranetUser = ($extranetIncluded ? !CExtranet::isIntranetUser() : false);
		}

		$groupPageURLTemplate = Option::get('socialnetwork', 'workgroups_page', SITE_DIR.'workgroups/', SITE_ID).'group/#group_id#/';

		$groupFilter = array();

		if (!empty($searchString))
		{
			$groupFilter['%NAME'] = $searchString;
		}

		if ($extranetUser)
		{
			$userGroupList = [];
			$res = UserToGroupTable::getList(array(
				'filter' => array(
				   'USER_ID' => $USER->getId(),
				   '@ROLE' => UserToGroupTable::getRolesMember()
				),
				'select' => array('GROUP_ID')
			));

			while($relation = $res->fetch())
			{
				$userGroupList[] = (int)$relation['GROUP_ID'];
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
			while ($group = $res->fetch())
			{
				if (!empty($group['NAME']))
				{
					$group['NAME'] = Emoji::decode($group['NAME']);
				}
				if (!empty($group['DESCRIPTION']))
				{
					$group['DESCRIPTION'] = Emoji::decode($group['DESCRIPTION']);
				}
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
				$res = UserToGroupTable::getList(array(
																		   'filter' => array(
																			   'USER_ID' => $USER->getId(),
																			   '@GROUP_ID' => $groupIdList,
																			   '@ROLE' => UserToGroupTable::getRolesMember()
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
					'NAME' => htmlspecialcharsbx($group['NAME']),
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

	private function convertAjaxToClientDb($arEntity, $entityType): array
	{
		static $timestamp = false;

		if (!$timestamp)
		{
			$timestamp = time();
		}

		$result = array();
		if ($entityType === 'sonetgroups')
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
		elseif($entityType === 'menuitems')
		{
			$result = array(
				'id' => 'M'.$arEntity["URL"],
				'entityId' => $arEntity["URL"],
				'name' => $arEntity["NAME"]
			);
			if (
				!empty($arEntity["CHAIN"])
				&& is_array($arEntity["CHAIN"])
			)
			{
				$result['chain'] = $arEntity["CHAIN"];
			}
			$result['checksum'] = md5(serialize($result));
			$result['timestamp'] = $timestamp;
		}
		elseif($entityType === 'users')
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

	private function getMenuItems($searchString = false): array
	{
		global $APPLICATION;

		$result = array();

		$isBitrix24 = file_exists($_SERVER["DOCUMENT_ROOT"] . SITE_DIR . ".superleft.menu_ext.php");
		$menuTypes = $isBitrix24 ? ['superleft', 'left', 'sub'] : ['top', 'left', 'sub'];

		$arMenuResult = $APPLICATION->includeComponent(
			'bitrix:menu',
			'left_vertical',
			[
				'MENU_TYPES' => $menuTypes,
				'MENU_CACHE_TYPE' => 'Y',
				'MENU_CACHE_TIME' => '604800',
				'MENU_CACHE_USE_GROUPS' => 'N',
				'MENU_CACHE_USE_USERS' => 'Y',
				'CACHE_SELECTED_ITEMS' => 'N',
				'MENU_CACHE_GET_VARS' => [],
				'MAX_LEVEL' => '3',
				'USE_EXT' => 'Y',
				'DELAY' => 'N',
				'ALLOW_MULTI_SELECT' => 'N',
				'RETURN' => 'Y',
			],
			false,
			["HIDE_ICONS" => "Y"]
		);

		$itemCache = [];
		foreach($arMenuResult as $menuItem)
		{
			if (empty($menuItem['LINK']))
			{
				continue;
			}

			if (
				empty($searchString)
				|| mb_strpos(ToLower($menuItem['TEXT']), ToLower($searchString)) !== false
			)
			{
				$url = isset($menuItem['PARAMS']) && isset($menuItem['PARAMS']["real_link"]) ?
					$menuItem['PARAMS']["real_link"] :
					$menuItem['LINK']
				;

				$hash = md5($menuItem['TEXT'] . '~' . $url);
				if (isset($itemCache[$hash]))
				{
					continue;
				}

				$itemCache[$hash] = true;

				$chain = (
					!empty($menuItem['CHAIN']) && is_array($menuItem['CHAIN'])
						? $menuItem['CHAIN']
						: [ $menuItem['TEXT'] ]
				);

				$chain = array_map(static function($item) {
					return htmlspecialcharsback($item);
				}, $chain);

				$result[] = array(
					'NAME' => $menuItem['TEXT'],
					'URL' => $url,
					'CHAIN' => $chain,
					'MODULE_ID' => '',
					'PARAM1' => '',
					'ITEM_ID' => 'M'.$menuItem['LINK'],
					'ICON' => '',
					'ON_CLICK' => $menuItem['PARAMS']['onclick'] ?? '',
				);
			}
		}

		usort($result, array(__CLASS__, "resultCmp"));

		return $result;
	}

	private function customSearch($searchString): void
	{
		static $bSocialnetworkIncluded = null;
		static $bExtranetSite = null;

		if ($bSocialnetworkIncluded === null)
		{
			$bSocialnetworkIncluded = Loader::includeModule('socialnetwork');
		}

		if ($bExtranetSite === null)
		{
			$bExtranetSite = (Loader::includeModule('extranet') && CExtranet::IsExtranetSite());
		}

		for($i = 0; $i < $this->arParams["NUM_CATEGORIES"]; $i++)
		{
			$categoryCode = $this->arParams["CATEGORY_".$i];

			if (is_array($categoryCode))
			{
				$categoryCode = $categoryCode[0];
			}

			if (mb_strpos($categoryCode, 'custom_') === 0)
			{
				$categoryTitle = trim($this->arParams["CATEGORY_".$i."_TITLE"]);
				if(empty($categoryTitle))
					continue;

				$this->arResult["CATEGORIES"][$i] = array(
					"TITLE" => htmlspecialcharsbx($categoryTitle),
					"ITEMS" => array()
				);

				if (
					$categoryCode === 'custom_users'
					&& !$bExtranetSite
				)
				{
					$this->arResult["customUsersCategoryId"] = $i;
					$this->arResult["CATEGORIES"][$i]["ITEMS"] = $this->getUsers($searchString);

					if ($this->arResult["customResultEmpty"] && !empty($this->arResult["CATEGORIES"][$i]["ITEMS"]))
					{
						$this->arResult["customResultEmpty"] = false;
					}

					foreach($this->arResult["CATEGORIES"][$i]["ITEMS"] as $key => $arItem)
					{
						$clientDbItem = $this->convertAjaxToClientDb($arItem, 'users');
						$this->arResult["CATEGORIES"][$i]["ITEMS"][$key]['CHECKSUM'] = $clientDbItem['checksum'];
					}
				}
				elseif (
					$categoryCode === 'custom_sonetgroups'
					&& $bSocialnetworkIncluded
				)
				{
					$this->arResult["customSonetGroupsCategoryId"] = $i;
					$this->arResult["CATEGORIES"][$i]["ITEMS"] = $this->getSonetGroups($searchString);

					if ($this->arResult["customResultEmpty"] && !empty($this->arResult["CATEGORIES"][$i]["ITEMS"]))
					{
						$this->arResult["customResultEmpty"] = false;
					}

					foreach($this->arResult["CATEGORIES"][$i]["ITEMS"] as $key => $arItem)
					{
						$clientDbItem = $this->convertAjaxToClientDb($arItem, 'sonetgroups');
						$this->arResult["CATEGORIES"][$i]["ITEMS"][$key]['CHECKSUM'] = $clientDbItem['checksum'];
					}
				}
				elseif ($categoryCode === 'custom_menuitems')
				{
					$this->arResult["CATEGORIES"][$i]["ITEMS"] = $this->getMenuItems($searchString);

					if ($this->arResult["customResultEmpty"] && !empty($this->arResult["CATEGORIES"][$i]["ITEMS"]))
					{
						$this->arResult["customResultEmpty"] = false;
					}

					foreach($this->arResult["CATEGORIES"][$i]["ITEMS"] as $key => $arItem)
					{
						$clientDbItem = $this->convertAjaxToClientDb($arItem, 'menuitems');
						$this->arResult["CATEGORIES"][$i]["ITEMS"][$key]['CHECKSUM'] = $clientDbItem['checksum'];
					}
				}
			}
		}
	}

	private function resultCmp($a, $b)
	{
		if ($a['NAME'] == $b['NAME'])
		{
			return 0;
		}
		return ($a['NAME'] < $b['NAME']) ? -1 : 1;
	}

	private function prepareCategories()
	{
		for($i = 0; $i < $this->arParams["NUM_CATEGORIES"]; $i++)
		{
			$categoryCode = $this->arParams["CATEGORY_".$i];

			if (is_array($categoryCode))
			{
				$categoryCode = $categoryCode[0];
			}

			$categoryTitle = trim($this->arParams["CATEGORY_".$i."_TITLE"]);
			if(empty($categoryTitle))
				continue;

			switch($categoryCode)
			{
				case "custom_users":
					$prefix = 'U';
					break;
				case "custom_sonetgroups":
					$prefix = 'G';
					break;
				case "custom_menuitems":
					$prefix = 'M';
					break;
				default:
					$prefix = '';
			}

			$this->arResult["CATEGORIES_ALL"][$i] = array(
				"TITLE" => htmlspecialcharsbx($categoryTitle),
				"CODE" => $categoryCode,
				"CLIENTDB_PREFIX" => $prefix
			);
		}

		if (!empty($this->arResult["query"]))
		{
			if (!empty($_REQUEST["get_all"]))
			{
				$entitiesList = array();
				$entity = $_REQUEST["get_all"];
				if ($entity === 'sonetgroups')
				{
					$sonetGroupsList = $this->getSonetGroups();
					foreach($sonetGroupsList as $group)
					{
						$entitiesList['G'.$group['ID']] = $this->convertAjaxToClientDb($group, $entity);
					}
				}
				elseif ($entity === 'menuitems')
				{
					$menuItemsList = $this->getMenuItems();
					foreach($menuItemsList as $menuItem)
					{
						$entitiesList['M'.$menuItem['URL']] = $this->convertAjaxToClientDb($menuItem, $entity);
					}
				}

				$this->arResult['ALLENTITIES'] = $entitiesList;
			}

			$this->arResult["customUsersCategoryId"] = $this->arResult["customSonetGroupsCategoryId"] = false;
			$this->arResult["customResultEmpty"] = true;

			$searchString = ($this->arResult["alt_query"] ? $this->arResult["alt_query"] : $this->arResult["query"]);

			$this->customSearch($searchString);

			if (
				$this->arResult["customResultEmpty"]
				&& $searchString == $this->arResult["alt_query"]
				&& $this->arResult["alt_query"] != $this->arResult["query"]
			) // if alt_query is guessed by mistake
			{
				$this->customSearch($this->arResult["query"]);
			}

			unset($this->arResult["customResultEmpty"]);

			for($i = 0; $i < $this->arParams["NUM_CATEGORIES"]; $i++)
			{
				$categoryCode = $this->arParams["CATEGORY_".$i];

				if (is_array($categoryCode))
				{
					$categoryCode = $categoryCode[0];
				}

				if (
					mb_strpos($categoryCode, 'custom_') === 0
					&& empty($this->arResult["CATEGORIES"][$i]["ITEMS"])
				)
				{
					unset($this->arResult["CATEGORIES"][$i]);
				}
			}

			if (
				!empty($this->arResult["CATEGORIES"]["others"])
				&& !empty($this->arResult["CATEGORIES"]["others"]["ITEMS"])
			)
			{
				foreach($this->arResult["CATEGORIES"]["others"]["ITEMS"] as $itemId => $arItem)
				{
					if (
						(int)$this->arResult["customUsersCategoryId"] > 0
						&& !empty($this->arResult["CATEGORIES"][$this->arResult["customUsersCategoryId"]]["ITEMS"])
						&& $arItem['MODULE_ID'] === 'intranet'
						&& preg_match('/^U(\d+)$/i', $arItem['ITEM_ID'], $matches)
					)
					{
						foreach($this->arResult["CATEGORIES"][$this->arResult["customUsersCategoryId"]]["ITEMS"] as $arUserItem)
						{
							if ($arItem['ITEM_ID'] == $arUserItem['ITEM_ID'])
							{
								unset($this->arResult["CATEGORIES"]["others"]["ITEMS"][$itemId]);
								break;
							}
						}
					}

					if (
						(int)$this->arResult["customSonetGroupsCategoryId"] > 0
						&& !empty($this->arResult["CATEGORIES"][$this->arResult["customSonetGroupsCategoryId"]]["ITEMS"])
						&& $arItem['MODULE_ID'] === 'socialnetwork'
						&& preg_match('/^G(\d+)$/i', $arItem['ITEM_ID'], $matches)
					)
					{
						foreach($this->arResult["CATEGORIES"][$this->arResult["customSonetGroupsCategoryId"]]["ITEMS"] as $arSonetGroupItem)
						{
							if ($arItem['ITEM_ID'] == $arSonetGroupItem['ITEM_ID'])
							{
								unset($this->arResult["CATEGORIES"]["others"]["ITEMS"][$itemId]);
								break;
							}
						}
					}
				}

				$this->arResult["CATEGORIES"]["others"]["ITEMS"] = array_values($this->arResult["CATEGORIES"]["others"]["ITEMS"]);
				if (empty($this->arResult["CATEGORIES"]["others"]["ITEMS"]))
				{
					unset($this->arResult["CATEGORIES"]["others"]);
				}

				foreach($this->arResult["CATEGORIES"] as $code => $category)
				{
					if (in_array($code, array('all', 'custom_users', 'custom_sonetgroups', 'custom_menugroups')))
					{
						continue;
					}

					if (
						!empty($this->arResult["CATEGORIES"][$code]["ITEMS"])
						&& is_array($this->arResult["CATEGORIES"][$code]["ITEMS"])
					)
					{
						foreach($this->arResult["CATEGORIES"][$code]["ITEMS"] as $key => $item)
						{
							if (isset($item["URL"]))
							{
								$this->arResult["CATEGORIES"][$code]["ITEMS"][$key]["URL"] = htmlspecialcharsBack($item["URL"]);
							}
						}
					}
				}
			}
		}

		unset(
			$this->arResult["customUsersCategoryId"],
			$this->arResult["customSonetGroupsCategoryId"]
		);
	}

	private function prepateGlobalSearchCategories(): void
	{
		global $USER, $CACHE_MANAGER;

		$this->arResult["GLOBAL_SEARCH_CATEGORIES"] = array();

		$this->arResult["IS_EXTRANET_SITE"] = (
			Loader::includeModule('extranet')
				? (SITE_ID === \CExtranet::getExtranetSiteID())
				: false
		);

		$globalSearchCategories = array(
			"stream" => array(
				"url" => ($this->arResult["IS_EXTRANET_SITE"] ? SITE_DIR : SITE_DIR."stream/")."?".(\Bitrix\Main\Composite\Helper::isOn() ? "ncc=1&" : "")."apply_filter=Y&FIND=",
				"text" => GetMessage("CT_BST_GLOBAL_SEARCH_NEWS")
			),
			"tasks" => array(
				"url" => ($this->arResult["IS_EXTRANET_SITE"] ? SITE_DIR."contacts/" : SITE_DIR."company/")."personal/user/".$USER->GetID()."/tasks/?apply_filter=Y&with_preset=Y&FIND=",
				"text" => GetMessage("CT_BST_GLOBAL_SEARCH_TASKS")
			)
		);

		if (!$this->arResult["IS_EXTRANET_SITE"])
		{
			$globalSearchCategories["calendar"] = array(
				"url" => ($this->arResult["IS_EXTRANET_SITE"] ? SITE_DIR."contacts/" : SITE_DIR."company/")."personal/user/".$USER->GetID()."/calendar/?apply_filter=Y&with_preset=Y&FIND=",
				"text" => GetMessage("CT_BST_GLOBAL_SEARCH_CALENDAR")
			);
		}

		$globalCrmSearchCategories = [];

		if (Loader::includeModule("crm") && CCrmPerms::IsAccessEnabled())
		{
			$cache = new \CPHPCache;
			$cacheId = "CRM_SEARCH_TITLE_".$USER->GetID();
			$cacheDir = "/crm/search_title_".substr(md5($USER->GetID()), -2)."/".$USER->GetID()."/";

			if($cache->initCache(7200, $cacheId, $cacheDir))
			{
				$cacheVars = $cache->getVars();
				$globalCrmSearchCategories = $cacheVars["CRM_SEARCH_CATEGORIES"];
			}
			else
			{
				$cache->startDataCache();
				$CACHE_MANAGER->StartTagCache($cacheDir);
				$CACHE_MANAGER->RegisterTag('crm_change_role');

				$isAdmin = CCrmPerms::IsAdmin();
				$userPermissions = CCrmPerms::GetCurrentUserPermissions();

				if (CCrmLead::CheckReadPermission(0, $userPermissions)) {
					$leadPaths = array(
						EntityViewSettings::LIST_VIEW => CrmCheckPath('PATH_TO_LEAD_LIST', "", SITE_DIR . 'crm/lead/list/'),
						EntityViewSettings::KANBAN_VIEW => CrmCheckPath('PATH_TO_LEAD_KANBAN', "", SITE_DIR . 'crm/lead/kanban/')
					);
					$currentView = LeadSettings::getCurrent()->getCurrentListViewID();
					$leadPath = $leadPaths[$currentView] ?? $leadPaths[EntityViewSettings::LIST_VIEW];

					$globalCrmSearchCategories["lead"] = array(
						"url" => $leadPath . "?apply_filter=Y&with_preset=Y&FIND=",
						"text" => GetMessage("CT_BST_GLOBAL_SEARCH_CRM_LEAD")
					);
				}

				if (CCrmDeal::CheckReadPermission(0, $userPermissions)) {
					$dealPaths = array(
						EntityViewSettings::LIST_VIEW => CrmCheckPath('PATH_TO_DEAL_LIST', "", SITE_DIR . 'crm/deal/list/'),
						EntityViewSettings::KANBAN_VIEW => CrmCheckPath('PATH_TO_DEAL_KANBAN', "", SITE_DIR . 'crm/deal/kanban/')
					);
					$currentView = DealSettings::getCurrent()->getCurrentListViewID();
					$dealPath = ($dealPaths[$currentView] ?? $dealPaths[EntityViewSettings::LIST_VIEW]);

					$globalCrmSearchCategories["deal"] = array(
						"url" => $dealPath . "?apply_filter=Y&with_preset=Y&FIND=",
						"text" => GetMessage("CT_BST_GLOBAL_SEARCH_CRM_DEAL")
					);
				}

				$crm = \Bitrix\Intranet\Integration\Crm::getInstance();
				if ($crm->isOldInvoicesEnabled() && ($isAdmin || !$userPermissions->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'READ')))
				{
					$invoicePaths = array(
						EntityViewSettings::LIST_VIEW => CrmCheckPath('PATH_TO_INVOICE_LIST', "", SITE_DIR . 'crm/invoice/list/'),
						EntityViewSettings::KANBAN_VIEW => CrmCheckPath('PATH_TO_INVOICE_KANBAN', "", SITE_DIR . 'crm/invoice/kanban/')
					);

					$currentView = InvoiceSettings::getCurrent()->getCurrentListViewID();
					$invoicePath = ($invoicePaths[$currentView] ?? $invoicePaths[EntityViewSettings::LIST_VIEW]);

					$globalCrmSearchCategories["invoice"] = array(
						"url" => $invoicePath . "?apply_filter=Y&with_preset=Y&FIND=",
						"text" => \CCrmOwnerType::GetCategoryCaption(\CCrmOwnerType::Invoice),
					);
				}

				if ($crm->isSmartInvoicesEnabled() && $crm->checkReadPermissions(\CCrmOwnerType::SmartInvoice))
				{
					$listUrl = $crm->getItemListUrlInCurrentView(\CCrmOwnerType::SmartInvoice);
					if ($listUrl)
					{
						$listUrl->addParams([
							'apply_filter' => 'Y',
							'with_preset' => 'Y',
							'FIND' => '',
						]);
					}

					$globalCrmSearchCategories[mb_strtolower(\CCrmOwnerType::SmartInvoiceName)] = [
						'url' => (string)$listUrl,
						'text' => \CCrmOwnerType::GetCategoryCaption(\CCrmOwnerType::SmartInvoice),
					];
				}

				if ($isAdmin || CCrmQuote::CheckReadPermission(0, $userPermissions))
				{
					$quotePaths = array(
						EntityViewSettings::LIST_VIEW => CrmCheckPath('PATH_TO_QUOTE_LIST', "", SITE_DIR . 'crm/quote/list/'),
						EntityViewSettings::KANBAN_VIEW => CrmCheckPath('PATH_TO_QUOTE_KANBAN', "", SITE_DIR . 'crm/quote/kanban/')
					);
					$currentView = QuoteSettings::getCurrent()->getCurrentListViewID();
					$quotePath = $quotePaths[$currentView] ?? $quotePaths[EntityViewSettings::LIST_VIEW];

					$globalCrmSearchCategories["quote"] = array(
						"url" => $quotePath . "?apply_filter=Y&with_preset=Y&FIND=",
						"text" => GetMessage("CT_BST_GLOBAL_SEARCH_CRM_QUOTE")
					);
				}

				if ($isAdmin || CCrmContact::CheckReadPermission(0, $userPermissions))
				{
					$globalCrmSearchCategories["contact"] = array(
						"url" => SITE_DIR . "crm/contact/list/?apply_filter=Y&with_preset=Y&FIND=",
						"text" => GetMessage("CT_BST_GLOBAL_SEARCH_CRM_CONTACT")
					);
				}

				if ($isAdmin || CCrmCompany::CheckReadPermission(0, $userPermissions))
				{
					$globalCrmSearchCategories["company"] = array(
						"url" => SITE_DIR . "crm/company/list/?apply_filter=Y&with_preset=Y&FIND=",
						"text" => GetMessage("CT_BST_GLOBAL_SEARCH_CRM_COMPANY")
					);
				}

				$globalCrmSearchCategories["activity"] = array(
					"url" => SITE_DIR . "crm/activity/list/?apply_filter=Y&with_preset=Y&FIND=",
					"text" => GetMessage("CT_BST_GLOBAL_SEARCH_CRM_ACTIVITY")
				);

				$CACHE_MANAGER->EndTagCache();
				$cache->endDataCache(array(
					"CRM_SEARCH_CATEGORIES" => $globalCrmSearchCategories
				));
			}
		}

		$globalSearchCategories = array_merge($globalSearchCategories, $globalCrmSearchCategories);

		if (Loader::includeModule("lists") && CLists::isFeatureEnabled())
		{
			$globalSearchCategories["processes"] = array(
				"url" => ($this->arResult["IS_EXTRANET_SITE"] ? SITE_DIR."contacts/" : SITE_DIR."company/")."personal/processes/?apply_filter=Y&with_preset=Y&FIND=",
				"text" => GetMessage("CT_BST_GLOBAL_SEARCH_PROCESS")
			);
		}

		if (ModuleManager::isModuleInstalled("disk"))
		{
			$globalSearchCategories["disk"] = array(
				"url" => SITE_DIR."company/personal/user/".$USER->GetID()."/disk/path/?apply_filter=Y&with_preset=Y&FIND=",
				"text" => GetMessage("CT_BST_GLOBAL_SEARCH_DISK")
			);
		}

		if (
			!$this->arResult["IS_EXTRANET_SITE"]
			&& ModuleManager::isModuleInstalled("landing")
		)
		{
			$globalSearchCategories["sites"] = array(
				"url" => SITE_DIR."sites/?apply_filter=Y&with_preset=Y&FIND=",
				"text" => GetMessage("CT_BST_GLOBAL_SEARCH_SITE")
			);
		}

		//preset
		$presetId = CUserOptions::GetOption("intranet", "left_menu_preset_".SITE_ID);
		if (!$presetId)
		{
			$presetId = COption::GetOptionString("intranet", "left_menu_preset", "");
		}
		$sort = array("stream", "tasks", "calendar", "disk", "lead", "deal", "invoice", "smart_invoice", "contact", "company", "quote", "activity", "sites", "processes");
		switch ($presetId)
		{
			case "tasks":
				$sort = array("tasks", "stream", "calendar", "disk", "lead", "deal", "invoice", "smart_invoice", "contact", "company", "quote", "activity", "sites", "processes");

				break;
			case "crm":
				$sort = array("lead", "deal", "invoice", "smart_invoice", "contact", "company", "quote", "activity", "tasks",  "calendar", "stream", "disk", "sites", "processes");

				break;
			case "sites":
				$sort = array("sites", "lead", "deal", "invoice", "smart_invoice", "contact", "company", "quote", "activity", "tasks", "stream",  "calendar",  "disk", "processes");

				break;
		}

		foreach ($sort as $key)
		{
			if (!isset($globalSearchCategories[$key]))
			{
				continue;
			}

			$this->arResult["GLOBAL_SEARCH_CATEGORIES"][$key] = $globalSearchCategories[$key];
		}

		//add unsorted categories at the end of the list
		$this->arResult["GLOBAL_SEARCH_CATEGORIES"] += $globalSearchCategories;
	}

	public function executeComponent()
	{
		global $APPLICATION;

		$this->arResult["CATEGORIES"] = array();

		$query = ltrim($_POST["q"] ?? '');
		if(
			!empty($query)
			&& $_REQUEST["ajax_call"] === "y"
			&& (
				!isset($_REQUEST["INPUT_ID"])
				|| $_REQUEST["INPUT_ID"] == $this->arParams["INPUT_ID"]
			)
		)
		{
			CUtil::decodeURIComponent($query);

			$this->arResult["alt_query"] = "";
			$this->arResult["query"] = $query;

			$this->arParams["NUM_CATEGORIES"] = (int)$this->arParams["NUM_CATEGORIES"];
			if ($this->arParams["NUM_CATEGORIES"] <= 0)
			{
				$this->arParams["NUM_CATEGORIES"] = 1;
			}

			$this->arParams["TOP_COUNT"] = (int)$this->arParams["TOP_COUNT"];
			if ($this->arParams["TOP_COUNT"] <= 0)
			{
				$this->arParams["TOP_COUNT"] = 5;
			}

			for ($i = 0; $i < $this->arParams["NUM_CATEGORIES"]; $i++)
			{
				$bCustom = true;
				if (is_array($this->arParams["CATEGORY_".$i]))
				{
					foreach ($this->arParams["CATEGORY_".$i] as $categoryCode)
					{
						if ((mb_strpos($categoryCode, 'custom_') !== 0))
						{
							$bCustom = false;
							break;
						}
					}
				}
				else
				{
					$bCustom = (mb_strpos($this->arParams["CATEGORY_".$i], 'custom_') === 0);
				}

				if ($bCustom)
				{
					continue;
				}

				$category_title = trim($this->arParams["CATEGORY_".$i."_TITLE"]);
				if (empty($category_title))
				{
					if (is_array($this->arParams["CATEGORY_".$i]))
					{
						$category_title = implode(", ", $this->arParams["CATEGORY_".$i]);
					}
					else
					{
						$category_title = trim($this->arParams["CATEGORY_".$i]);
					}
				}
				if (empty($category_title))
				{
					continue;
				}

				$this->arResult["CATEGORIES"][$i] = array(
					"TITLE" => htmlspecialcharsbx($category_title),
					"ITEMS" => array()
				);
			}

			$this->arResult['CATEGORIES_ITEMS_EXISTS'] = false;
			foreach ($this->arResult["CATEGORIES"] as $category)
			{
				if (!empty($category['ITEMS']) && is_array($category['ITEMS']))
				{
					$this->arResult['CATEGORIES_ITEMS_EXISTS'] = true;
					break;
				}
			}
		}

		$this->prepareCategories();
		$this->prepateGlobalSearchCategories();

		if (
			isset($_REQUEST["ajax_call"])
			&& $_REQUEST["ajax_call"] === "y"
			&& (
				!isset($_REQUEST["INPUT_ID"])
				|| $_REQUEST["INPUT_ID"] == $this->arParams["INPUT_ID"]
			)
		)
		{
			$APPLICATION->RestartBuffer();

			if(!empty($query))
			{
				$this->IncludeComponentTemplate('ajax');
			}
			CMain::FinalActions();
		}

		CUtil::InitJSCore(array('ajax'));
		$this->IncludeComponentTemplate();
	}
}
