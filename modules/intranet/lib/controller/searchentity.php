<?php

namespace Bitrix\Intranet\Controller;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Intranet\ActionFilter;
use Bitrix\Intranet\Integration\Socialnetwork\Chat\GroupChat;
use Bitrix\Intranet\Integration\Socialnetwork\Url\GroupUrl;
use Bitrix\Main\Text\Emoji;

class SearchEntity extends \Bitrix\Main\Engine\Controller
{
	const ENTITY_SONETGROUPS = 'sonetgroups';
	const ENTITY_MENUITEMS = 'menuitems';

	protected function getDefaultPreFilters()
	{
		$preFilters = parent::getDefaultPreFilters();
		$preFilters[] = new ActionFilter\UserType([
			'employee',
			'extranet',
		]);

		return $preFilters;
	}

	private static function getAllEntities(): array
	{
		return array(
			self::ENTITY_SONETGROUPS,
			self::ENTITY_MENUITEMS
		);
	}

	public function getAllAction($entity)
	{
		$entity = trim($entity);

		if ($entity == '')
		{
			$this->addError(new Error(Loc::getMessage('INTRANET_CONTROLLER_SEARCHENTITY_GETALL_ENTITY_EMPTY'), 'INTRANET_CONTROLLER_SEARCHENTITY_GETALL_ENTITY_EMPTY'));
			return null;
		}
		if (!in_array($entity, self::getAllEntities()))
		{
			$this->addError(new Error(Loc::getMessage('INTRANET_CONTROLLER_SEARCHENTITY_GETALL_ENTITY_INCORRECT'), 'INTRANET_CONTROLLER_SEARCHENTITY_GETALL_ENTITY_INCORRECT'));
			return null;
		}


		$items = array();

		if ($entity == self::ENTITY_SONETGROUPS)
		{
			$sonetGroupsList = self::getSonetGroups();
			foreach($sonetGroupsList as $group)
			{
				$items['G'.$group['ID']] = self::convertAjaxToClientDb($group, $entity);
			}
		}
		elseif ($entity == self::ENTITY_MENUITEMS)
		{
			$menuItemsList = self::getMenuItems();
			foreach($menuItemsList as $menuItem)
			{
				$items['M'.$menuItem['URL']] = self::convertAjaxToClientDb($menuItem, $entity);
			}
		}

		return array(
			'items' => $items
		);
	}

	private static function getSonetGroups($searchString = false)
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
			$extranetSiteId = ($extranetIncluded ? \CExtranet::getExtranetSiteID() : false);
			$extranetUser = ($extranetIncluded ? !\CExtranet::isIntranetUser() : false);
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
		elseif (!\CSocNetUser::IsCurrentUserModuleAdmin(SITE_ID, false))
		{
			$groupFilter['CHECK_PERMISSIONS'] = $USER->GetId();
		}

		$cacheResult = $obCache = false;

		if (empty($searchString))
		{
			$cacheTtl = 3153600;
			$cacheId = 'search_title_sonetgroups_'.md5(serialize($groupFilter).$extranetSiteId.$groupPageURLTemplate);
			$cacheDir = '/intranet/search/sonetgroups_v2/';

			$obCache = new \CPHPCache;
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

			$res = \CSocnetGroup::getList(
				array('NAME' => 'ASC'),
				$groupFilter,
				false,
				false,
				array("ID", "NAME", "IMAGE_ID", "DESCRIPTION", 'TYPE')
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

			$chatIds = GroupChat::getChatIds($groupIdList);

			foreach($groupList as $group)
			{
				$image = \CFile::ResizeImageGet(
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
				$rsGroupSite = \CSocNetGroup::GetSite($group["ID"]);
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

				$chatId = $chatIds[(int)$group['ID']] ?? 0;

				$result[] = array(
					'ID' => $group['ID'],
					'NAME' => htmlspecialcharsbx($group['NAME']),
					'URL' => GroupUrl::get(
						(int)$group['ID'],
						(string)$group['TYPE'],
						$chatId
					),
					'MODULE_ID' => '',
					'PARAM1' => '',
					'ITEM_ID' => 'G'.$group['ID'],
					'ICON' => empty($image['src'])? '': $image['src'],
					'TYPE' => 'sonetgroups',
					'IS_EXTRANET' => $isExtranet,
					'SITE' => $site,
					'IS_MEMBER' => in_array($group['ID'], $memberGroupIdList),
					'GROUP_TYPE' => $group['TYPE'],
					'GROUP_CHAT_ID' => $chatId,
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

	private static function convertAjaxToClientDb($arEntity, $entityType)
	{
		static $timestamp = false;

		if (!$timestamp)
		{
			$timestamp = time();
		}

		$result = [];
		if ($entityType === 'sonetgroups' || $entityType === 'collabs')
		{
			$result = [
				'id' => 'G'.$arEntity["ID"],
				'entityId' => $arEntity["ID"],
				'name' => $arEntity["NAME"],
				'avatar' => empty($arEntity['ICON'])? '': $arEntity['ICON'],
				'desc' => empty($arEntity['DESCRIPTION'])? '': (TruncateText($arEntity['DESCRIPTION'], 100)),
				'isExtranet' => ($arEntity['IS_EXTRANET'] ? "Y" : "N"),
				'site' => $arEntity['SITE'],
				'isMember' => (isset($arEntity['IS_MEMBER']) && $arEntity['IS_MEMBER'] ? "Y" : "N"),
				'groupType' => $arEntity['GROUP_TYPE'],
				'dialogId' => GroupUrl::getDialogId((int)$arEntity['GROUP_CHAT_ID'])
			];
			$result['checksum'] = md5(serialize($result));
			$result['timestamp'] = $timestamp;
		}
		elseif($entityType == 'menuitems')
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

	private static function getMenuItems($searchString = false)
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
			['HIDE_ICONS' => 'Y']
		);

		$itemCache = [];
		foreach($arMenuResult as $menuItem)
		{
			if (empty($menuItem['LINK']))
				continue;

			if (
				empty($searchString)
				|| mb_strpos(mb_strtolower($menuItem['TEXT']), mb_strtolower($searchString)) !== false
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
					'NAME' => htmlspecialcharsbx($menuItem['TEXT']),
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

	private static function resultCmp($a, $b)
	{
		if ($a['NAME'] == $b['NAME'])
		{
			return 0;
		}
		return ($a['NAME'] < $b['NAME']) ? -1 : 1;
	}
}
