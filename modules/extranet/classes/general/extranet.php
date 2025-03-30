<?php

use Bitrix\Extranet\Service\ServiceContainer;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Web\Uri;
use Bitrix\Socialnetwork\UserToGroupTable;

class CExtranet
{
	public static function IsExtranetSite($site_id = SITE_ID): bool
	{
		if (!$site_id)
		{
			$site_id = SITE_ID;
		}

		return ($site_id === Option::get('extranet', 'extranet_site'));
	}

	public static function GetExtranetSiteID()
	{
		$extranet_site_id = COption::GetOptionString("extranet", "extranet_site");
		if (
			($extranet_site_id <> '')
			&& CSite::GetArrayByID($extranet_site_id)
		)
		{
			return $extranet_site_id;
		}

		return false;
	}

	public static function GetExtranetUserGroupID()
	{
		$extranet_group_id = (int)Option::get('extranet', 'extranet_group');
		if ($extranet_group_id > 0)
		{
			return $extranet_group_id;
		}

		return false;
	}

	public static function OnUserLogout($ID)
	{
		unset(\Bitrix\Main\Application::getInstance()->getKernelSession()["aExtranetUser_".$ID]);
	}

	public static function IsIntranetUser($site = SITE_ID, $userID = 0)
	{
		global $USER;

		static $staticCache = [];

		if(!is_int($userID))
		{
			$userID = (int)$userID;
		}

		if($userID > 0)
		{
			if (isset($staticCache[$userID]))
			{
				$result = $staticCache[$userID];
			}
			else
			{
				$rsUser = CUser::GetList(
					'ID',
					'ASC',
					array("ID_EQUAL_EXACT" => $userID),
					array("FIELDS" => array("ID"), "SELECT" => array("UF_DEPARTMENT"))
				);
				$arUser = $rsUser->Fetch();

				$result = $staticCache[$userID] = (
					is_array($arUser)
					&& isset($arUser["UF_DEPARTMENT"][0])
					&& $arUser["UF_DEPARTMENT"][0] > 0
				);
			}

			return $result;
		}

		if(!(
			isset($USER)
			&& is_object($USER)
			&& ((get_class($USER) === 'CUser') || ($USER instanceof CUser))
			&& $USER->IsAuthorized()
		))
		{
			return false;
		}

		$userID = $USER->GetID();
		if($userID <= 0)
		{
			return false;
		}

		if(isset(\Bitrix\Main\Application::getInstance()->getKernelSession()["aExtranetUser_{$userID}"][$site]))
		{
			return true;
		}

		if(
			$USER->IsAdmin()
			|| (
				Loader::includeModule('socialnetwork')
				&& CSocNetUser::IsCurrentUserModuleAdmin($site)
			)
		)
		{
			\Bitrix\Main\Application::getInstance()->getKernelSession()["aExtranetUser_{$userID}"][$site] = true;
			return true;
		}

		if (isset($staticCache[$userID]))
		{
			$result = $staticCache[$userID];
		}
		else
		{
			$rsUser = CUser::GetList(
				'ID',
				'ASC',
				array("ID_EQUAL_EXACT" => $userID),
				array("FIELDS" => array("ID"), "SELECT" => array("UF_DEPARTMENT"))
			);

			$arUser = $rsUser->Fetch();
			$result = $staticCache[$userID] = (
				is_array($arUser)
				&& isset($arUser["UF_DEPARTMENT"][0])
				&& $arUser["UF_DEPARTMENT"][0] > 0
			);

			if ($result)
			{
				\Bitrix\Main\Application::getInstance()->getKernelSession()["aExtranetUser_{$userID}"][$site] = true;
			}
		}

		return $result;
	}

	public static function IsExtranetUser(): bool // deprecated
	{
		global $USER;

		return (
			is_object($USER)
			&& $USER->IsAuthorized()
			&& in_array(self::GetExtranetUserGroupID(), $USER->GetUserGroupArray())
		);
	}

	public static function IsExtranetSocNetGroup($groupID): bool
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return false;
		}

		$extranet_site_id = self::GetExtranetSiteID();
		$arGroupSites = [];

		$rsGroupSite = CSocNetGroup::GetSite($groupID);
		while($arGroupSite = $rsGroupSite->Fetch())
		{
			$arGroupSites[] = $arGroupSite["LID"];
		}

		return (in_array($extranet_site_id, $arGroupSites, true));
	}

	public static function IsExtranetAdmin()
	{
		global $USER;

		if (is_object($USER) && $USER->IsAdmin())
		{
			return true;
		}

		if (is_object($USER) && !$USER->IsAuthorized())
		{
			return false;
		}

		static $isExtAdmin = 'no';
		if($isExtAdmin === 'no')
		{
			$arGroups = $USER->GetUserGroupArray();
			$iExtGroups = CExtranet::GetExtranetUserGroupID();

			$arSubGroups = CGroup::GetSubordinateGroups($arGroups);
			if (in_array($iExtGroups, $arSubGroups))
			{
				$isExtAdmin = true;
				return true;
			}

			if (
				Loader::includeModule('socialnetwork')
				&& CSocNetUser::IsCurrentUserModuleAdmin(SITE_ID, false)
			)
			{
				$isExtAdmin = true;
				return true;
			}

			$isExtAdmin = false;
			return false;
		}

		return $isExtAdmin;
	}

	public static function ExtranetRedirect()
	{
		global $USER, $APPLICATION;

		$curPage = $APPLICATION->GetCurPageParam();
		$scriptFile = \Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getScriptFile();

		if(
			(!defined("ADMIN_SECTION") || ADMIN_SECTION !== true)
			&& (!defined("EXTRANET_NO_REDIRECT") || EXTRANET_NO_REDIRECT !== true)
			&& (mb_strpos($curPage, "/bitrix/") !== 0)
			&& (mb_strpos($curPage, "/upload/") !== 0)
			&& (mb_strpos($curPage, "/oauth/") !== 0)
			&& (mb_strpos($curPage, "/desktop_app/") !== 0)
			&& (mb_strpos($curPage, "/docs/pub/") !== 0)
			&& (mb_strpos($curPage, "/extranet/confirm/") !== 0)
			&& (mb_strpos($curPage, "/mobile/ajax.php") !== 0)
			&& (mb_strpos($curPage, "/mobile/mobile_component/") !== 0)
			&& (mb_strpos($curPage, "/mobile/web_mobile_component/") !== 0)
			&& (mb_strpos($curPage, "/mobileapp/") !== 0)
			&& (mb_strpos($curPage, "/pub/") !== 0)
			&& (mb_strpos($curPage, "/rest/") !== 0)
			&& (!self::IsExtranetSite())
			&& self::GetExtranetSiteID() <> ''
			&& $USER->IsAuthorized()
			&& !$USER->IsAdmin()
			&& !self::IsIntranetUser()
			&& self::isExtranetUser()
			&& !($USER->CanDoFileOperation(
				'fm_view_file',
				[
					SITE_ID,
					$scriptFile
				]
			) && ($scriptFile !== '/desktop_app/router.php'))
		)
		{
			$rsSites = CSite::GetByID(self::GetExtranetSiteID());
			if (
				($arExtranetSite = $rsSites->Fetch())
				&& ($arExtranetSite["ACTIVE"] !== "N")
			)
			{
				$URLToRedirect = false;
				$userId = (int)$USER->GetID();
				$isCollaber = ServiceContainer::getInstance()->getCollaberService()->isCollaberById($userId);

				$userSEFFolder = COption::GetOptionString("socialnetwork", "user_page", SITE_DIR . 'company/personal/', SITE_ID);
				$workgroupSEFFolder = COption::GetOptionString("socialnetwork", "workgroups_page", SITE_DIR . 'workgroups/', SITE_ID);

				if ($userSEFFolder && str_starts_with($curPage, $userSEFFolder))
				{
					$userSEFFolderExtranet = COption::GetOptionString("socialnetwork", "user_page", $arExtranetSite["DIR"] . "contacts/personal/", $arExtranetSite['LID']);
					if ($userSEFFolderExtranet)
					{
						$URLToRedirect = $userSEFFolderExtranet.mb_substr($curPage, mb_strlen($userSEFFolder));
					}
				}
				elseif ($workgroupSEFFolder && str_starts_with($curPage, $workgroupSEFFolder))
				{
					$workgroupSEFFolderExtranet = COption::GetOptionString("socialnetwork", "workgroups_page", $arExtranetSite["DIR"] . "workgroups/", $arExtranetSite['LID']);
					if ($workgroupSEFFolderExtranet)
					{
						$URLToRedirect = $workgroupSEFFolderExtranet.mb_substr($curPage, mb_strlen($workgroupSEFFolder));
					}
				}

				if (!$URLToRedirect)
				{
					$URLToRedirect = ($arExtranetSite["SERVER_NAME"] <> '' ? (CMain::IsHTTPS() ? "https" : "http") . "://" . $arExtranetSite["SERVER_NAME"] : "") . $arExtranetSite["DIR"];

					if ($isCollaber && str_ends_with($URLToRedirect, '/'))
					{
						$uri = (new Uri($URLToRedirect . 'online/'));

						if (preg_match("/^\\/online\\/([\\.\\-0-9a-zA-Z]+)(\\/?)([^\\/]*)$/i", $curPage, $matches))
						{
							$alias = $matches[1] ?? null;

							if ($alias)
							{
								$uri->addParams(['alias' => $alias]);
							}
						}

						if (preg_match('/^\/call\/(?:\?callId=|detail\/)(\d+)/', $curPage, $matches))
						{
							$uri = (new Uri($URLToRedirect . 'call/detail/' . $matches[1]));
						}

						if (preg_match('/^\/video\/([a-zA-Z0-9]+)/', $curPage, $matches))
						{
							$uri = (new Uri($URLToRedirect . 'video/' . $matches[1]));
						}

						$URLToRedirect = $uri->getLocator();
					}
				}

				$urlParams = array();

				if (
					($urlParts = parse_url($curPage))
					&& !empty($urlParts['query'])
				)
				{
					$keyWhiteList = [ 'IM_SETTINGS' ];

					$pairsList = explode('&', $urlParts['query']);
					foreach ($pairsList as $pair)
					{
						[ $key, $value ] = explode('=', $pair);
						if (in_array($key, $keyWhiteList, true))
						{
							$urlParams[$key] = $value;
						}
					}
				}

				if (!empty($urlParams))
				{
					$URLToRedirect = CHTTP::urlAddParams($URLToRedirect, $urlParams);
				}

				LocalRedirect($URLToRedirect, true, '307 Temporary Redirect');
			}
		}
	}

	public static function GetMyGroupsUsers($site, $bGadget = false, $bOnlyActive = true)
	{
		global $USER, $obUsersCache;

		if (
			mb_strlen($site) < 0
			|| !isset($USER)
			|| !($USER instanceof CUser)
		)
		{
			return [];
		}

		$arUsersInMyGroups = $obUsersCache->get($site, $bGadget);

		if (is_array($arUsersInMyGroups))
		{
			return $arUsersInMyGroups;
		}

		$arUsersInMyGroups = [];
		$arUserSocNetGroups = [];

		if (
			Loader::includeModule('socialnetwork')
			&& (
				!CSocNetUser::IsCurrentUserModuleAdmin()
				|| $bGadget
			)
		)
		{
			$dbUsersInGroup = CSocNetUserToGroup::GetList(
				[],
				[
					'USER_ID' => $USER->GetID(),
					'<=ROLE' => SONET_ROLES_USER,
					'GROUP_SITE_ID' => $site,
					'GROUP_ACTIVE' => 'Y'
				],
				false,
				false,
				['ID', 'GROUP_ID']
			);

			if ($dbUsersInGroup)
			{
				while ($arUserInGroup = $dbUsersInGroup->GetNext())
				{
					$arUserSocNetGroups[] = $arUserInGroup['GROUP_ID'];
				}
			}

			if (count($arUserSocNetGroups) > 0)
			{
				$arFilter = [
					'@GROUP_ID' => $arUserSocNetGroups,
					'<=ROLE' => SONET_ROLES_USER
				];

				if ($bOnlyActive)
				{
					$arFilter['USER_ACTIVE'] = 'Y';
				}

				$dbUsersInGroup = CSocNetUserToGroup::GetList(
					[],
					$arFilter,
					false,
					false,
					['ID', 'USER_ID']
				);

				if ($dbUsersInGroup)
				{
					while ($arUserInGroup = $dbUsersInGroup->GetNext())
					{
						$arUsersInMyGroups[] = $arUserInGroup['USER_ID'];
					}
				}
			}
		}
		else
		{
			$dbUsers = CUser::GetList(
				'ID',
				'ASC',
				[
					'ACTIVE' => 'Y',
					'GROUPS_ID' => [ self::GetExtranetUserGroupID() ],
				]
			);

			if ($dbUsers)
			{
				while ($arUser = $dbUsers->GetNext())
				{
					$arUsersInMyGroups[] = $arUser['ID'];
				}
			}
		}

		if (count($arUsersInMyGroups) > 0)
		{
			$arUsersInMyGroups = array_unique($arUsersInMyGroups);
		}

		$obUsersCache->set($site, $bGadget, $arUsersInMyGroups);

		return $arUsersInMyGroups;
	}

	/**
	* Returns array of IDs of the users who belong to current user's socialnetwork groups
	* In comparison with CExtranet::GetMyGroupsUsers it doesn't check if the user is sonet admin
	* and returns the same result for admin and user
	* This function was added because of the modified extranet users visibility logic
	* @param string $extranetSite - extranet SITE_ID (usually CExtranet::GetExtranetSiteID())
	* @return array IDs of the users in the groups
	*/
	public static function getMyGroupsUsersSimple($extranetSite, $params = array()): array
	{
		global $USER, $obUsersCache;

		$result = array();

		if (mb_strlen($extranetSite) < 0)
		{
			return $result;
		}

		$userId = 0;

		if (
			is_array($params)
			&& isset($params['userId'])
			&& (int)$params['userId'] > 0
		)
		{
			$userId = (int)$params['userId'];
		}
		elseif (
			is_object($USER)
			&& $USER->isAuthorized()
		)
		{
			$userId = $USER->getId();
		}

		if ($userId <= 0)
		{
			return $result;
		}

		$arUsersInMyGroups = $obUsersCache->get($extranetSite, false, $userId);

		if (is_array($arUsersInMyGroups))
		{
			return $arUsersInMyGroups;
		}

		$arUsersInMyGroups = array();

		if (Loader::includeModule('socialnetwork'))
		{
			$query = new Query(UserToGroupTable::getEntity());
			$query->setSelect(array('GROUP_ID'));
			$query->setFilter(array(
				'<=ROLE' => UserToGroupTable::ROLE_USER,
				'=USER_ID' => $userId,
				'=GROUP.ACTIVE' => 'Y',
				'=GROUP.WorkgroupSite:GROUP.SITE_ID' => $extranetSite
			));

			$subQuery = $query->getQuery();

			$res = UserToGroupTable::getList(array(
				'order' => array(),
				'filter' => array(
					'@GROUP_ID' => new \Bitrix\Main\DB\SqlExpression($subQuery),
					'<=ROLE' => UserToGroupTable::ROLE_USER,
					'=USER.ACTIVE' => 'Y',
				),
				'select' => array('USER_ID'),
				'group' => array('USER_ID')
			));

			while ($arUserInGroup = $res->fetch())
			{
				$arUsersInMyGroups[] = $arUserInGroup["USER_ID"];
			}
		}

		$obUsersCache->SetForKey($userId."_".$extranetSite."_N", $arUsersInMyGroups);

		return $arUsersInMyGroups;
	}

	public static function GetMyGroupsUsersFull($site, $bNotCurrent = false, $bGadget = false): array
	{
		global $USER;

		$arUsersInMyGroups = [];

		$arUsersInMyGroupsID = self::GetMyGroupsUsers($site, $bGadget);
		if (count($arUsersInMyGroupsID) > 0)
		{
			$strUsersInMyGroupsID = "(".implode(" | ", $arUsersInMyGroupsID).")";
			if ($bNotCurrent)
			{
				$strUsersInMyGroupsID .= " ~".$USER->GetID();
			}

			$arFilter = Array("ID"=>$strUsersInMyGroupsID);

			$rsUsers = CUser::GetList("ID", "asc", $arFilter, array("SELECT"=>array("UF_*")));

			while($arUser = $rsUsers->GetNext())
			{
				$arUsersInMyGroups[] = $arUser;
			}

			return $arUsersInMyGroups;
		}

		return [];
	}

	public static function GetExtranetGroupUsers($full = false): array
	{
		$arExtranetGroupUsers = array();

		$arFilter = [
			'GROUPS_ID' => [ self::GetExtranetUserGroupID() ],
		];

		$rsUsers = CUser::GetList("ID", "asc", $arFilter);
		while($arUser = $rsUsers->GetNext())
		{
			if ($full)
			{
				$arExtranetGroupUsers[] = $arUser;
			}
			else
			{
				$arExtranetGroupUsers[] = $arUser["ID"];
			}
		}

		return $arExtranetGroupUsers;
	}

	public static function GetPublicUsers($full = false): array
	{
		global $USER;

		$arPublicUsers = array();
		$arFilter = [
			Option::get("extranet", "extranet_public_uf_code", "UF_PUBLIC") => "1",
			"ID" => "~".$USER->GetID(),
			"!UF_DEPARTMENT" => false,
			"GROUPS_ID" => [ self::GetExtranetUserGroupID() ],
		];

		$rsUsers = CUser::GetList("ID", "asc", $arFilter);
		while($arUser = $rsUsers->GetNext())
		{
			if ($full)
			{
				$arPublicUsers[] = $arUser;
			}
			else
			{
				$arPublicUsers[] = $arUser["ID"];
			}
		}

		return $arPublicUsers;
	}

	public static function GetIntranetUsers()
	{
		static $CACHE = false;

		if (!$CACHE)
		{
			$arIntranetUsers = array();

			$ttl = (defined("BX_COMP_MANAGED_CACHE") ? 2592000 : 600);
			$cache_id = 'users';
			$obCache = new CPHPCache;
			$cache_dir = '/bitrix/extranet/';

			if($obCache->InitCache($ttl, $cache_id, $cache_dir))
			{
				$tmpVal = $obCache->GetVars();
				$arIntranetUsers = $tmpVal['USERS'];
				unset($tmpVal);
			}
			else
			{
				global $CACHE_MANAGER;

				if (defined("BX_COMP_MANAGED_CACHE"))
				{
					$CACHE_MANAGER->StartTagCache($cache_dir);
					$CACHE_MANAGER->RegisterTag('intranet_users');
				}

				$rsUsers = CUser::GetList(
					'ID',
					'ASC',
					Array(
						"!UF_DEPARTMENT" => false
					),
					array(
						"FIELDS" => array("ID"),
						"SELECT" => array("UF_DEPARTMENT"),
					)
				);

				while($arUser = $rsUsers->Fetch())
				{
					$arIntranetUsers[] = $arUser["ID"];
				}

				if (defined("BX_COMP_MANAGED_CACHE"))
				{
					$CACHE_MANAGER->EndTagCache();
				}

				if($obCache->StartDataCache())
				{
					$obCache->EndDataCache(array(
						'USERS' => $arIntranetUsers,
					));
				}
			}

			$CACHE = $arIntranetUsers;
		}
		else
		{
			$arIntranetUsers = $CACHE;
		}

		return $arIntranetUsers;
	}

	public static function IsProfileViewable($arUser, $site_id = false, $bOnlyActive = true, $arContext = array()): bool
	{
		global $USER;

		if (isset($arUser['EXTERNAL_AUTH_ID']))
		{
			if ($arUser['EXTERNAL_AUTH_ID'] === 'replica')
			{
				return true;
			}

			if ($arUser['EXTERNAL_AUTH_ID'] === 'email')
			{
				return false;
			}
		}

		// if current user is admin
		if (self::IsExtranetAdmin())
		{
			return true;
		}

		// if extranet site is not set
		if (!self::GetExtranetSiteID())
		{
			return true;
		}

		// if current user is not authorized
		if (!$USER->IsAuthorized())
		{
			return false;
		}

		// if intranet and current user is not employee
		if (
			!self::IsExtranetSite($site_id)
			&& !self::IsIntranetUser()
		)
		{
			return false;
		}

		$bNeedCheckContext = false;

		// if intranet and profile user is not employee
		if (!self::IsExtranetSite($site_id))
		{
			if (
				(
					(
						!is_array($arUser["UF_DEPARTMENT"])
						&& (int)$arUser["UF_DEPARTMENT"] > 0
					)
					|| (
						is_array($arUser["UF_DEPARTMENT"])
						&& (int)$arUser["UF_DEPARTMENT"][0] > 0
					)
				)
				&& self::IsIntranetUser()
			)
			{
				return true;
			}

			$arUsersInMyGroupsID = self::GetMyGroupsUsers(self::GetExtranetSiteID(), false, $bOnlyActive);
			if (
				!in_array($arUser["ID"], $arUsersInMyGroupsID)
				&& ((int)$arUser["ID"] !== (int)$USER->GetID())
			)
			{
				$bNeedCheckContext = true;
			}
		}

		// if extranet and profile user not public
		if (self::IsExtranetSite($site_id))
		{
			if ((int)$arUser[COption::GetOptionString("extranet", "extranet_public_uf_code", "UF_PUBLIC")] === 1)
			{
				return true;
			}

			$arUsersInMyGroupsID = self::GetMyGroupsUsers(SITE_ID);
			if (
				!in_array($arUser["ID"], $arUsersInMyGroupsID)
				&& ((int)$arUser["ID"] !== (int)$USER->GetID())
			)
			{
				$bNeedCheckContext = true;
			}
		}

		if ($bNeedCheckContext)
		{
			if (
				isset($arContext["ENTITY_TYPE"], $arContext["ENTITY_ID"])
				&& $arContext["ENTITY_TYPE"] === "LOG_ENTRY"
				&& (int)$arContext["ENTITY_ID"] > 0
			)
			{
				return CSocNetUser::CheckContext(
					$USER->GetID(),
					$arUser["ID"],
					array_merge($arContext, [ 'SITE_ID' => self::GetExtranetSiteID() ])
				);
			}

			return false;
		}

		return true;
	}

	public static function IsProfileViewableByID($user_id, $site_id = false): bool
	{
		if (
			self::IsExtranetAdmin()
			||
			(
				ModuleManager::isModuleInstalled('bitrix24')
				&& CSocNetUser::IsCurrentUserModuleAdmin(SITE_ID, false)
			)
		)
		{
			return true;
		}

		if ((int)$user_id > 0 && self::GetExtranetSiteID() <> '')
		{
			$dbUser = CUser::GetByID($user_id);
			$arUser = $dbUser->Fetch();

			if (!self::IsProfileViewable($arUser, $site_id))
			{
				return false;
			}
		}

		return true;
	}

	public static function ModifyGroupDefaultFeatures(&$arSocNetFeaturesSettings, $site_id = false)
	{
		if (self::IsExtranetSite($site_id))
		{
			$arSocNetFeaturesSettings["calendar"]["operations"]["write"][SONET_ENTITY_GROUP] = SONET_ROLES_USER;
			$arSocNetFeaturesSettings["files"]["operations"]["write_limited"][SONET_ENTITY_GROUP] = SONET_ROLES_USER;
			$arSocNetFeaturesSettings["blog"]["operations"]["write_post"][SONET_ENTITY_GROUP] = SONET_ROLES_USER;
		}
	}


	public static function OnBeforeSocNetGroupUpdateHandler($ID, $arFields): bool
	{
		global $bArchiveBeforeUpdate, $APPLICATION;

		if (!isset($arFields["CLOSED"]))
		{
			return true;
		}

		if (!Loader::includeModule('socialnetwork'))
		{
			return false;
		}

		$arSocNetGroup = CSocNetGroup::GetByID($ID);
		if (!$arSocNetGroup)
		{
			$APPLICATION->ThrowException(GetMessage("SONET_NO_GROUP"), "ERROR_NO_GROUP");
			return false;
		}

		if (Loader::includeModule('extranet'))
		{
			$ExtranetSiteID = self::GetExtranetSiteID();
			$arGroupSites = array();

			$rsGroupSite = CSocNetGroup::GetSite($ID);
			while($arGroupSite = $rsGroupSite->Fetch())
			{
				$arGroupSites[] = $arGroupSite["LID"];
			}

			if (!in_array($ExtranetSiteID, $arGroupSites, true))
			{
				return true;
			}
		}
		else
		{
			return true;
		}

		$bArchiveBeforeUpdate = ($arSocNetGroup["CLOSED"] === "Y");

		return true;
	}

	public static function OnSocNetGroupUpdateHandler($ID, $arFields): bool
	{
		global $bArchiveBeforeUpdate, $APPLICATION;

		if (!isset($arFields["CLOSED"]))
		{
			return true;
		}

		if ((int)$ID <= 0)
		{
			return false;
		}

		if (!Loader::includeModule('socialnetwork'))
		{
			return false;
		}

		if (Loader::includeModule('extranet'))
		{
			$arSocNetGroup = CSocNetGroup::GetByID($ID);
			if (!$arSocNetGroup)
			{
				$APPLICATION->ThrowException(Loc::getMessage("SONET_NO_GROUP"), "ERROR_NO_GROUP");
				return false;
			}

			$ExtranetSiteID = self::GetExtranetSiteID();
			$arGroupSites = array();

			$rsGroupSite = CSocNetGroup::GetSite($ID);
			while($arGroupSite = $rsGroupSite->Fetch())
			{
				$arGroupSites[] = $arGroupSite["LID"];
			}

			if (!in_array($ExtranetSiteID, $arGroupSites))
			{
				return true;
			}
		}
		else
		{
			return true;
		}

		$bFromArchiveToOpen = $bFromOpenToArchive = false;
		if ($arFields["CLOSED"] === "Y" && !$bArchiveBeforeUpdate)
		{
			$bFromOpenToArchive = true;
		}
		elseif ($arFields["CLOSED"] !== "Y" && $bArchiveBeforeUpdate)
		{
			$bFromArchiveToOpen = true;
		}

		$arEmail = array();

		if ($bFromOpenToArchive || $bFromArchiveToOpen)
		{
			$dbRequests = CSocNetUserToGroup::GetList(
				array(),
				array(
					"GROUP_ID" => $ID,
					"<=ROLE" => SONET_ROLES_USER,
					"USER_ACTIVE" => "Y"
				),
				false,
				array(),
				array("ID", "USER_ID", "USER_NAME", "USER_LAST_NAME", "USER_EMAIL")
			);

			if ($dbRequests)
			{
				while ($arRequests = $dbRequests->GetNext())
				{
					$arEmail[] = array(
						"NAME" => $arRequests["USER_NAME"],
						"LAST_NAME" => $arRequests["USER_LAST_NAME"],
						"EMAIL" => $arRequests["USER_EMAIL"]
					);
				}
			}
		}

		if ($bFromOpenToArchive)
		{
			foreach($arEmail as $recipient)
			{
				$arEventFields = array(
					"WG_ID" => $ID,
					"WG_NAME" => $arFields["NAME"],
					"MEMBER_NAME" => $recipient["NAME"],
					"MEMBER_LAST_NAME" => $recipient["LAST_NAME"],
					"MEMBER_EMAIL" => $recipient["EMAIL"],
				);

				CEvent::Send("EXTRANET_WG_TO_ARCHIVE", SITE_ID, $arEventFields);
			}
		}

		if ($bFromArchiveToOpen)
		{
			foreach($arEmail as $recipient)
			{
				$arEventFields = array(
					"WG_ID" => $ID,
					"WG_NAME" => $arFields["NAME"],
					"MEMBER_NAME" => $recipient["NAME"],
					"MEMBER_LAST_NAME" => $recipient["LAST_NAME"],
					"MEMBER_EMAIL" => $recipient["EMAIL"],
				);

				CEvent::Send("EXTRANET_WG_FROM_ARCHIVE", SITE_ID, $arEventFields);
			}
		}

		return true;
	}

	/*
	RegisterModuleDependences('socialnetwork', 'OnSocNetUserToGroupAdd', 'extranet', 'CExtranet', 'OnSocNetUserToGroupAdd');
	*/
	public static function OnSocNetUserToGroupAdd($ID, $arFields): bool
	{
		if(!defined("BX_COMP_MANAGED_CACHE"))
		{
			return true;
		}

		if (
			array_key_exists("ROLE", $arFields)
			&& array_key_exists("GROUP_ID", $arFields)
			&& (int)$arFields["GROUP_ID"] > 0
			&& (int)$arFields["USER_ID"] > 0
		)
		{
			if (!Loader::includeModule('socialnetwork'))
			{
				return false;
			}

			$dbUsersInGroup = CSocNetUserToGroup::GetList(
				array(),
				array(
					"GROUP_ID" => $arFields["GROUP_ID"],
					"<=ROLE" => SONET_ROLES_USER,
				),
				false,
				false,
				array("ID", "USER_ID")
			);

			$userIdList = [
				(int)$arFields['USER_ID'],
			];

			if ($dbUsersInGroup)
			{
				while ($arUserInGroup = $dbUsersInGroup->fetch())
				{
					$userIdList[] = (int)$arUserInGroup['USER_ID'];
				}
			}

			static::clearCache($userIdList);
		}

		return true;
	}

	/*
	RegisterModuleDependences('socialnetwork', 'OnSocNetUserToGroupUpdate', 'extranet', 'CExtranet', 'OnSocNetUserToGroupUpdate');
	*/
	public static function OnSocNetUserToGroupUpdate($ID, $arFields): bool
	{
		if(!defined("BX_COMP_MANAGED_CACHE"))
		{
			return true;
		}

		if (
			array_key_exists("ROLE", $arFields)
			&& array_key_exists("GROUP_ID", $arFields)
			&& (int)$arFields["GROUP_ID"] > 0
			&& (int)$arFields["USER_ID"] > 0
		)
		{
			if (!Loader::includeModule('socialnetwork'))
			{
				return false;
			}

			$dbUsersInGroup = CSocNetUserToGroup::GetList(
				array(),
				array(
					"GROUP_ID" => $arFields["GROUP_ID"],
					"<=ROLE" => SONET_ROLES_USER,
				),
				false,
				false,
				array("ID", "USER_ID")
			);

			$userIdList = [
				(int)$arFields['USER_ID'],
			];

			if ($dbUsersInGroup)
			{
				while ($arUserInGroup = $dbUsersInGroup->fetch())
				{
					$userIdList[] = (int)$arUserInGroup['USER_ID'];
				}
			}

			static::clearCache($userIdList);
		}

		return true;
	}

	/*
	RegisterModuleDependences('socialnetwork', 'OnSocNetUserToGroupDelete', 'extranet', 'CExtranet', 'OnSocNetUserToGroupDelete');
	*/
	public static function OnSocNetUserToGroupDelete($ID): bool
	{
		if(!defined("BX_COMP_MANAGED_CACHE"))
		{
			return true;
		}

		if (!Loader::includeModule('socialnetwork'))
		{
			return false;
		}

		$arUser2Group = CSocNetUserToGroup::GetByID($ID);
		if (!$arUser2Group)
		{
			return true;
		}

		if (
			array_key_exists("GROUP_ID", $arUser2Group)
			&& array_key_exists("USER_ID", $arUser2Group)
			&& (int)$arUser2Group["GROUP_ID"] > 0
			&& (int)$arUser2Group["USER_ID"] > 0
		)
		{
			$dbUsersInGroup = CSocNetUserToGroup::GetList(
				array(),
				array(
					"GROUP_ID" => $arUser2Group["GROUP_ID"],
					"<=ROLE" => SONET_ROLES_USER,
				),
				false,
				false,
				array("ID", "USER_ID")
			);

			$userIdList = [
				(int)$arUser2Group['USER_ID'],
			];

			if ($dbUsersInGroup)
			{
				while ($arUserInGroup = $dbUsersInGroup->fetch())
				{
					$userIdList[] = (int)$arUserInGroup['USER_ID'];
				}
			}

			static::clearCache($userIdList);
		}

		return true;
	}

	/*
	RegisterModuleDependences('main', 'OnUserDelete', 'extranet', 'CExtranet', 'OnUserDelete', 10);
	*/
	public static function OnUserDelete($ID): bool
	{
		if(!defined("BX_COMP_MANAGED_CACHE"))
		{
			return true;
		}

		if ((int)$ID > 0)
		{
			if (!Loader::includeModule('socialnetwork'))
			{
				return false;
			}

			$dbUsersInGroup = CSocNetUserToGroup::GetList(
				array(),
				array(
					"USER_ID" => $ID,
					"<=ROLE" => SONET_ROLES_USER,
				),
				false,
				false,
				array("ID", "GROUP_ID")
			);

			$arUserSocNetGroups = [];

			if ($dbUsersInGroup)
			{
				while ($arUserInGroup = $dbUsersInGroup->GetNext())
				{
					$arUserSocNetGroups[] = $arUserInGroup["GROUP_ID"];
				}
			}

			$userIdList = [
				(int)$ID,
			];

			if (count($arUserSocNetGroups) > 0)
			{
				$dbUsersInGroup = CSocNetUserToGroup::GetList(
					array(),
					array(
						"@GROUP_ID" => $arUserSocNetGroups,
						"<=ROLE" => SONET_ROLES_USER,
					),
					false,
					false,
					array("ID", "USER_ID")
				);

				if ($dbUsersInGroup)
				{
					while ($arUserInGroup = $dbUsersInGroup->GetNext())
					{
						$userIdList[] = (int)$arUserInGroup["USER_ID"];
					}
				}
			}

			static::clearCache($userIdList);
		}

		return true;
	}

	/*
	RegisterModuleDependences('socialnetwork', 'OnSocNetGroupDelete', 'extranet', 'CExtranet', 'OnSocNetGroupDelete');
	*/
	public static function OnSocNetGroupDelete($ID): bool
	{
		if (!defined("BX_COMP_MANAGED_CACHE"))
		{
			return true;
		}

		if ((int)$ID > 0)
		{
			if (!Loader::includeModule('socialnetwork'))
			{
				return false;
			}

			$dbUsersInGroup = CSocNetUserToGroup::GetList(
				array(),
				array(
					"GROUP_ID" => $ID,
					"<=ROLE" => SONET_ROLES_USER,
					),
					false,
					false,
					array("ID", "USER_ID")
			);

			$userIdList = [];

			if ($dbUsersInGroup)
			{
				while ($arUserInGroup = $dbUsersInGroup->fetch())
				{
					$userIdList[] = (int)$arUserInGroup['USER_ID'];
				}
			}

			static::clearCache($userIdList);
		}

		return true;
	}

	/*
	RegisterModuleDependences('main', 'onBeforeUserAdd', 'extranet', 'CExtranet', 'ClearPublicUserCacheOnAddUpdate');
	RegisterModuleDependences('main', 'onBeforeUserUpdate', 'extranet', 'CExtranet', 'ClearPublicUserCacheOnAddUpdate');
	*/
	public static function ClearPublicUserCacheOnAddUpdate($arFields): bool
	{
		global $CACHE_MANAGER;

		$id = (int)(isset($arFields['ID']) ?? 0);
		if ($id > 0) // update
		{
			$dbRes = CUser::GetList(
				"id", "asc",
				array("ID_EQUAL_EXACT" => (int)$arFields['ID']),
				array('SELECT' => array('UF_PUBLIC'))
			);

			if (
				($arOldFields = $dbRes->Fetch()) && isset($arFields['UF_PUBLIC'])
				&& $arOldFields['UF_PUBLIC'] != $arFields['UF_PUBLIC']
			)
			{
				$CACHE_MANAGER->ClearByTag("extranet_public");
			}
		}
		elseif (isset($arFields['UF_PUBLIC'])) // add
		{
			$CACHE_MANAGER->ClearByTag("extranet_public");
		}

		return true;
	}

	/*
	RegisterModuleDependences('main', 'OnUserDelete', 'extranet', 'CExtranet', 'ClearPublicUserCacheOnDelete');
	*/
	public static function ClearPublicUserCacheOnDelete($ID): bool
	{
		global $CACHE_MANAGER;

		if ((int)$ID > 0)
		{
			$dbRes = CUser::GetList(
				"id", "asc",
				array("ID_EQUAL_EXACT" => (int)$ID),
				array('SELECT' => array('UF_PUBLIC'))
			);

			if (
				($arFields = $dbRes->Fetch())
				&& isset($arFields["UF_PUBLIC"])
				&& $arFields["UF_PUBLIC"]
			)
			{
				$CACHE_MANAGER->ClearByTag("extranet_public");
			}
		}

		return true;
	}

	public static function GetSitesByLogDestinations($arRights, $authorId = false, $explicit_site_id = false): array
	{
		static $extranet_site_id = null;
		static $default_site_id = null;

		static $arIntranetSiteID = null;

		$arSiteID = array();
		if (!is_array($arRights))
		{
			return $arSiteID;
		}

		if ($extranet_site_id === null)
		{
			$extranet_site_id = self::GetExtranetSiteID();
			$arIntranetSiteID = array();
			$rsSite = CSite::GetList("sort", "desc", array("ACTIVE" => "Y"));
			while ($arSite = $rsSite->Fetch())
			{
				if ($arSite["LID"] == $extranet_site_id)
				{
					continue;
				}
				$arIntranetSiteID[] = $arSite["LID"];
			}

			$default_site_id = CSite::GetDefSite();
		}

		$default_site_id = (
			$explicit_site_id
			&& $explicit_site_id != $extranet_site_id
				? $explicit_site_id
				: $default_site_id
		);

		$arUserId = array();
		$arSonetGroupId = array();

		foreach ($arRights as $right_tmp)
		{
			if (preg_match('/^U(\d+)$/', $right_tmp, $matches))
			{
				$arUserId[] = $matches[1];
			}
			elseif (
				preg_match('/^SG(\d+)$/', $right_tmp, $matches)
				|| preg_match('/^SG(\d+)_'.SONET_ROLES_OWNER.'$/', $right_tmp, $matches)
				|| preg_match('/^SG(\d+)_'.SONET_ROLES_MODERATOR.'$/', $right_tmp, $matches)
				|| preg_match('/^SG(\d+)_'.SONET_ROLES_USER.'$/', $right_tmp, $matches)
			)
			{
				$arSonetGroupId[] = $matches[1];
			}
		}

		if (!empty($arUserId))
		{
			$rsUser = CUser::GetList(
				'',
				'',
				[
					'UF_DEPARTMENT' => false,
					'ID' => implode('|', $arUserId),
					'=IS_REAL_USER' => 'Y',
				],
				[
					'FIELDS' => [ 'ID' ]
				]
			);

			$i = 0;
			$arExtranetUserId = array();

			while ($arUser = $rsUser->Fetch()) // if any extranet user
			{
				if ($i == 0)
				{
					$arSiteID[] = $extranet_site_id; // only once
				}
				$arExtranetUserId[] = $arUser["ID"];
			}

			$ar = array_diff($arUserId, $arExtranetUserId);
			if (!empty($ar)) // there are non-extranet_users
			{
				$arSiteID[] = $default_site_id;
			}
		}

		if (
			!empty($arSonetGroupId)
			&& Loader::includeModule('socialnetwork')
		)
		{
			$rsGroupSite = CSocNetGroup::GetSite($arSonetGroupId);
			while($arGroupSite = $rsGroupSite->Fetch())
			{
				$ar = array_diff($arIntranetSiteID, $arSiteID);
				if (
					empty($ar)
					&& in_array($extranet_site_id, $arSiteID)
				)
				{
					break;
				}

				if (!in_array($arGroupSite["LID"], $arSiteID))
				{
					$arSiteID[] = $arGroupSite["LID"];
				}
			}
		}

		$currentSiteId = ($explicit_site_id ?: SITE_ID);
		if (
			in_array($currentSiteId, $arIntranetSiteID)
			&& !in_array($currentSiteId, $arSiteID)
		)
		{
			$arSiteID[] = $currentSiteId;
		}

		return array_unique($arSiteID);
	}

	public static function WorkgroupsAllowed(): bool
	{
		return (Option::get('extranet', 'allow_groups', 'Y') === 'Y');
	}

	public static function ShowAllContactsAllowed(): bool
	{
		return (Option::get('extranet', 'show_all_contacts', 'N') === 'Y');
	}

	public static function fillUserListFilterORM($arParams = array(), &$arFilter = array(), &$bFilteredByUserId = null)
	{
		global $USER;

		static $IsShowAllContacts = false;
		static $IsExtranetWorkGroupsAllowed = false;

		if (!is_array($arParams))
		{
			$arParams = array();
		}

		$currentUserId = (int)($arParams["CURRENT_USER_ID"] ?? $USER->GetId());

		$bExtranetUser = (bool)($arParams["EXTRANET_USER"] ?? !self::IsIntranetUser(SITE_ID, $currentUserId));

		$arFilteredUserIDs = (
			isset($arParams["MY_USERS"])
			&& is_array($arParams["MY_USERS"])
				? $arParams["MY_USERS"]
				: []
		);

		if ($IsShowAllContacts === false)
		{
			$IsShowAllContacts = (
				self::ShowAllContactsAllowed()
				|| (
					isset($arParams['SHOW_ALL_EXTRANET_CONTACTS'])
					&& $arParams['SHOW_ALL_EXTRANET_CONTACTS']
					&& CSocNetUser::isUserModuleAdmin($currentUserId)
				)
					? "Y"
					: "N"
			);
		}

		if ($IsExtranetWorkGroupsAllowed === false)
		{
			$IsExtranetWorkGroupsAllowed = (
				self::WorkgroupsAllowed()
					? "Y"
					: "N"
			);
		}

		if (
			$IsExtranetWorkGroupsAllowed === "Y"
			&& (
				$bExtranetUser
				|| $IsShowAllContacts !== "Y"
			)
		)
		{
			$arFilteredUserIDs = array_merge($arFilteredUserIDs, self::GetMyGroupsUsers(self::GetExtranetSiteID()));
		}

		if ($bExtranetUser)
		{
			if ($IsExtranetWorkGroupsAllowed !== "Y")
			{
				$arFilter = false;
			}
			else
			{
				$arFilter["ID"] = array_unique(array_merge(array($currentUserId), $arFilteredUserIDs));
				$bFilteredByUserId = true;
			}
		}
		elseif ($IsShowAllContacts !== "Y")
		{
			$subFilter = [
				'!UF_DEPARTMENT' => false,
			];

			if ($IsExtranetWorkGroupsAllowed === "Y")
			{
				$subFilter['=EXTERNAL_AUTH_ID'] = 'email';
				$subFilter['ID'] = array_unique(array_merge([ $currentUserId ], $arFilteredUserIDs));

				$bFilteredByUserId = true;
			}

			if (isset($arParams['ALLOW_BOTS']) && $arParams['ALLOW_BOTS'] === true)
			{
				$subFilter['=EXTERNAL_AUTH_ID'] = 'bot';
			}

			if (count($subFilter) <= 1)
			{
				foreach($subFilter as $key => $value)
				{
					$arFilter[$key] = $value;
				}
			}
			else
			{
				$subFilter['LOGIC'] = 'OR';
				$arFilter[] = $subFilter;
			}
		}

		if ($arFilter)
		{
			if (
				isset($arParams["INTRANET_ONLY"])
				&& $arParams["INTRANET_ONLY"]
			)
			{
				if (isset($arFilter["UF_DEPARTMENT"]))
				{
					unset($arFilter["UF_DEPARTMENT"]);
				}
				$arFilter["!UF_DEPARTMENT"] = false;
			}
			elseif (
				isset($arParams["EXTRANET_ONLY"])
				&& $arParams["EXTRANET_ONLY"]
			)
			{
				if (isset($arFilter["!UF_DEPARTMENT"]))
				{
					unset($arFilter["!UF_DEPARTMENT"]);
				}
				$arFilter["UF_DEPARTMENT"] = false;
			}
		}
	}

	public static function OnGetProfileView($currentUserId, $arUser, $siteId, $arContext, $bOnlyActive = true): bool
	{
		global $USER;

		if ((int)$currentUserId === (int)$USER->getId())
		{
			return self::isProfileViewable($arUser, $siteId, $bOnlyActive, $arContext);
		}

		return false;
	}

	protected static function clearCache(array $userIdList = [])
	{
		global $CACHE_MANAGER;

		if (count($userIdList) > 50)
		{
			$CACHE_MANAGER->clearByTag('extranet_public');
		}
		else
		{
			foreach ($userIdList as $userId)
			{
				$CACHE_MANAGER->clearByTag('extranet_user_' . $userId);
			}
		}
	}
}

class CUsersInMyGroupsCache
{
	private $CACHE = array();

	function get($site, $bGadget = false, $userId = false)
	{
		global $USER;

		$result = false;

		if (mb_strlen($site) < 0)
		{
			return $result;
		}

		if (
			$userId === false
			&& is_object($USER)
			&& $USER->isAuthorized()
		)
		{
			$userId = $USER->getId();
		}

		$userId = (int)$userId;

		if (!$userId)
		{
			return $result;
		}

		if (
			array_key_exists($userId."_".$site."_".($bGadget ? "Y" : "N"), $this->CACHE)
			&& is_array($this->CACHE[$userId."_".$site."_".($bGadget ? "Y" : "N")])
		)
		{
			$result = $this->CACHE[$userId."_".$site."_".($bGadget ? "Y" : "N")];
		}

		return $result;
	}

	function set($site, $bGadget = false, $arValue = array()): bool
	{
		global $USER;

		if ($site == '')
		{
			return false;
		}

		if (!is_array($arValue))
		{
			return false;
		}

		$userId = false;
		if (
			is_object($USER)
			&& $USER->isAuthorized()
		)
		{
			$userId = $USER->getId();
		}

		if (!$userId)
		{
			return false;
		}

		return $this->setForKey($userId."_".$site."_".($bGadget ? "Y" : "N"), $arValue);
	}

	function setForKey($key, $arValue = array()): bool
	{
		if ($key == '')
		{
			return false;
		}

		if (!is_array($arValue))
		{
			return false;
		}

		$this->CACHE[$key] = $arValue;

		return true;
	}
}
