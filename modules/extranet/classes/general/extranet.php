<?
class CExtranet
{
	public static function IsExtranetSite($site_id = SITE_ID)
	{
		if (!$site_id)
			$site_id = SITE_ID;

		if ($site_id == COption::GetOptionString("extranet", "extranet_site"))
			return true;

		return false;
	}

	public static function GetExtranetSiteID()
	{
		$extranet_site_id = COption::GetOptionString("extranet", "extranet_site");
		if (strlen($extranet_site_id) > 0)
		{
			if(CSite::GetArrayByID($extranet_site_id))
				return $extranet_site_id;
		}
		return false;
	}

	public static function GetExtranetUserGroupID()
	{
		$extranet_group_id = COption::GetOptionInt("extranet", "extranet_group");
		if (intval($extranet_group_id) > 0)
		{
			return intval($extranet_group_id);
		}
		return false;
	}

	public static function OnUserLogout($ID)
	{
		unset($_SESSION["aExtranetUser_".$ID]);
	}

	public static function IsIntranetUser($site = SITE_ID, $userID = 0)
	{
		global $USER;

		static $staticCache = array();

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
					$o = "ID",
					$b = "ASC",
					array("ID_EQUAL_EXACT" => $userID),
					array("FIELDS" => array("ID"), "SELECT" => array("UF_DEPARTMENT"))
				);
				$arUser = $rsUser->Fetch();

				$result = $staticCache[$userID] = (
					is_array($arUser)
					&& isset($arUser["UF_DEPARTMENT"])
					&& isset($arUser["UF_DEPARTMENT"][0])
					&& $arUser["UF_DEPARTMENT"][0] > 0
				);
			}

			return $result;
		}

		if(!(
			isset($USER)
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

		if(isset($_SESSION["aExtranetUser_{$userID}"][$site]))
		{
			return true;
		}

		if(
			$USER->IsAdmin()
			|| (CModule::IncludeModule("socialnetwork") && CSocNetUser::IsCurrentUserModuleAdmin($site))
		)
		{
			$_SESSION["aExtranetUser_{$userID}"][$site] = true;
			return true;
		}

		if (isset($staticCache[$userID]))
		{
			$result = $staticCache[$userID];
		}
		else
		{
			$rsUser = CUser::GetList(
				$o = "ID",
				$b = "ASC",
				array("ID_EQUAL_EXACT" => $userID),
				array("FIELDS" => array("ID"), "SELECT" => array("UF_DEPARTMENT"))
			);

			$arUser = $rsUser->Fetch();
			$result = $staticCache[$userID] = (
				is_array($arUser)
				&& isset($arUser["UF_DEPARTMENT"])
				&& isset($arUser["UF_DEPARTMENT"][0])
				&& $arUser["UF_DEPARTMENT"][0] > 0
			);

			if ($result)
			{
				$_SESSION["aExtranetUser_{$userID}"][$site] = true;
			}
		}

		return $result;
	}

	public static function IsExtranetUser() // deprecated
	{
		global $USER;

		if (is_object($USER) && $USER->IsAuthorized())
		{
			if (in_array(CExtranet::GetExtranetUserGroupID(), $USER->GetUserGroupArray()))
			{
				return true;
			}
		}

		return false;
	}

	public static function IsExtranetSocNetGroup($groupID)
	{
		if (!CModule::IncludeModule("socialnetwork"))
		{
			return false;
		}

		$extranet_site_id = CExtranet::GetExtranetSiteID();
		$arGroupSites = array();

		$rsGroupSite = CSocNetGroup::GetSite($groupID);
		while($arGroupSite = $rsGroupSite->Fetch())
		{
			$arGroupSites[] = $arGroupSite["LID"];
		}

		return (in_array($extranet_site_id, $arGroupSites));
	}

	public static function IsExtranetAdmin()
	{
		global $USER;

		if (is_object($USER) && $USER->IsAdmin())
			return true;

		if (is_object($USER) && !$USER->IsAuthorized())
			return false;

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
				CModule::IncludeModule("socialnetwork")
				&& CSocNetUser::IsCurrentUserModuleAdmin(SITE_ID, false)
			)
			{
				$isExtAdmin = true;
				return true;
			}

			$isExtAdmin = false;
			return false;
		}
		else
		{
			return $isExtAdmin;
		}
	}

	public static function ExtranetRedirect()
	{
		global $USER, $APPLICATION;

		$curPage = $APPLICATION->GetCurPageParam();

		if(
			(!defined("ADMIN_SECTION") || ADMIN_SECTION !== true)
			&& (!defined("EXTRANET_NO_REDIRECT") || EXTRANET_NO_REDIRECT !== true)
			&& (strpos($curPage, "/bitrix/") !== 0)
			&& (strpos($curPage, "/upload/") !== 0)
			&& (strpos($curPage, "/oauth/") !== 0)
			&& (strpos($curPage, "/desktop_app/") !== 0)
			&& (strpos($curPage, "/docs/pub/") !== 0)
			&& (strpos($curPage, "/extranet/confirm/") !== 0)
			&& (strpos($curPage, "/mobile/ajax.php") !== 0)
			&& (strpos($curPage, "/mobile/mobile_component/") !== 0)
			&& (strpos($curPage, "/mobile/web_mobile_component/") !== 0)
			&& (strpos($curPage, "/mobileapp/") !== 0)
			&& (strpos($curPage, "/pub/") !== 0)
			&& (strpos($curPage, "/rest/") !== 0)
			&& !preg_match("/^\\/online\\/([\\.\\-0-9a-zA-Z]+)(\\/?)([^\\/]*)$/i", $curPage)
			&& (!CExtranet::IsExtranetSite())
		)
		{
			if (
				strlen(CExtranet::GetExtranetSiteID()) > 0
				&& $USER->IsAuthorized()
				&& !$USER->IsAdmin()
				&& !CExtranet::IsIntranetUser()
			)
			{
				$rsSites = CSite::GetByID(CExtranet::GetExtranetSiteID());
				if (
					($arExtranetSite = $rsSites->Fetch())
					&& ($arExtranetSite["ACTIVE"] != "N")
				)
				{
					$URLToRedirect = false;

					$userSEFFolder = COption::GetOptionString("socialnetwork", "user_page", false, SITE_ID);
					$workgroupSEFFolder = COption::GetOptionString("socialnetwork", "workgroups_page", false, SITE_ID);
					if (strpos($curPage, $userSEFFolder) === 0)
					{
						$userSEFFolderExtranet = COption::GetOptionString("socialnetwork", "user_page", false, $arExtranetSite['LID']);
						if ($userSEFFolderExtranet)
						{
							$URLToRedirect = $userSEFFolderExtranet.substr($curPage, strlen($userSEFFolder));
						}
					}
					elseif (strpos($curPage, $workgroupSEFFolder) === 0)
					{
						$workgroupSEFFolderExtranet = COption::GetOptionString("socialnetwork", "workgroups_page", false, $arExtranetSite['LID']);
						if ($workgroupSEFFolderExtranet)
						{
							$URLToRedirect = $workgroupSEFFolderExtranet.substr($curPage, strlen($workgroupSEFFolder));
						}
					}

					if (!$URLToRedirect)
					{
						$URLToRedirect = (strlen($arExtranetSite["SERVER_NAME"]) > 0 ? (CMain::IsHTTPS() ? "https" : "http") . "://" . $arExtranetSite["SERVER_NAME"] : "") . $arExtranetSite["DIR"];
					}

					$urlParams = array();

					if (
						($urlParts = parse_url($curPage))
						&& !empty($urlParts['query'])
					)
					{
						$keyWhiteList = array('IM_SETTINGS');


						$pairsList = explode('&', $urlParts['query']);
						foreach ($pairsList as $pair)
						{
							list($key, $value) = explode('=', $pair);
							if (in_array($key, $keyWhiteList))
							{
								$urlParams[$key] = $value;
							}
						}
					}

					if (!empty($urlParams))
					{
						$URLToRedirect = CHTTP::urlAddParams($URLToRedirect, $urlParams);
					}

					LocalRedirect($URLToRedirect, true);
				}
			}
		}
	}

	public static function GetMyGroupsUsers($site, $bGadget = false, $bOnlyActive = true)
	{
		global $USER, $obUsersCache;

		if (strlen($site) < 0)
		{
			return array();
		}

		$arUsersInMyGroups = $obUsersCache->get($site, $bGadget);

		if (is_array($arUsersInMyGroups))
		{
			return $arUsersInMyGroups;
		}

		$arUsersInMyGroups = array();
		$arUserSocNetGroups = array();

		if (
			CModule::IncludeModule("socialnetwork")
			&& (
				!CSocNetUser::IsCurrentUserModuleAdmin()
				|| $bGadget
			) 
		)
		{
			$dbUsersInGroup = CSocNetUserToGroup::GetList(
				array(),
				array(
					"USER_ID" => $USER->GetID(),
					"<=ROLE" => SONET_ROLES_USER,
					"GROUP_SITE_ID" => $site,
					"GROUP_ACTIVE" => "Y"
				),
				false,
				false,
				array("ID", "GROUP_ID")
			);

			if ($dbUsersInGroup)
			{
				while ($arUserInGroup = $dbUsersInGroup->GetNext())
				{
					$arUserSocNetGroups[] = $arUserInGroup["GROUP_ID"];
				}
			}

			if (count($arUserSocNetGroups) > 0)
			{
				$arFilter = array(
					"@GROUP_ID" => $arUserSocNetGroups,
					"<=ROLE" => SONET_ROLES_USER
				);

				if ($bOnlyActive)
				{
					$arFilter["USER_ACTIVE"] = "Y";
				}

				$dbUsersInGroup = CSocNetUserToGroup::GetList(
					array(),
					$arFilter,
					false,
					false,
					array("ID", "USER_ID")
				);

				if ($dbUsersInGroup)
				{
					while ($arUserInGroup = $dbUsersInGroup->GetNext())
					{
						$arUsersInMyGroups[] = $arUserInGroup["USER_ID"];
					}
				}
			}
		}
		else
		{
			$dbUsers = CUser::GetList(
				($by="id"),
				($order="asc"),
				array(
					"ACTIVE" => "Y",
					"GROUPS_ID" => array(CExtranet::GetExtranetUserGroupID())
				)
			);

			if ($dbUsers)
			{
				while ($arUser = $dbUsers->GetNext())
				{
					$arUsersInMyGroups[] = $arUser["ID"];
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
	public static function getMyGroupsUsersSimple($extranetSite, $params = array())
	{
		global $USER, $obUsersCache;

		$result = array();

		if (strlen($extranetSite) < 0)
		{
			return $result;
		}

		$userId = 0;

		if (
			is_array($params)
			&& isset($params['userId'])
			&& intval($params['userId']) > 0
		)
		{
			$userId = intval($params['userId']);
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

		if (CModule::IncludeModule('socialnetwork'))
		{
			$query = new \Bitrix\Main\Entity\Query(\Bitrix\Socialnetwork\UserToGroupTable::getEntity());
			$query->setSelect(array('GROUP_ID'));
			$query->setFilter(array(
				'<=ROLE' => \Bitrix\Socialnetwork\UserToGroupTable::ROLE_USER,
				'=USER_ID' => $userId,
				'=GROUP.ACTIVE' => 'Y',
				'=GROUP.WorkgroupSite:GROUP.SITE_ID' => $extranetSite
			));

			$subQuery = $query->getQuery();

			$res = \Bitrix\Socialnetwork\UserToGroupTable::getList(array(
				'order' => array(),
				'filter' => array(
					'@GROUP_ID' => new \Bitrix\Main\DB\SqlExpression($subQuery),
					'<=ROLE' => \Bitrix\Socialnetwork\UserToGroupTable::ROLE_USER,
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

	public static function GetMyGroupsUsersFull($site, $bNotCurrent = false, $bGadget = false)
	{

		global $USER;

		$arUsersInMyGroups = array();

		$arUsersInMyGroupsID = CExtranet::GetMyGroupsUsers($site, $bGadget);
		if (count($arUsersInMyGroupsID) > 0)
		{
			$strUsersInMyGroupsID = "(".implode(" | ", $arUsersInMyGroupsID).")";
			if ($bNotCurrent)
				$strUsersInMyGroupsID .= " ~".$USER->GetID();

			$arFilter = Array("ID"=>$strUsersInMyGroupsID);

			$rsUsers = CUser::GetList(($by="ID"), ($order="asc"), $arFilter, array("SELECT"=>array("UF_*")));

			while($arUser = $rsUsers->GetNext())
				$arUsersInMyGroups[] = $arUser;

			return $arUsersInMyGroups;
		}
		else
			return array();

	}

	public static function GetExtranetGroupUsers($full = false)
	{
		$arExtranetGroupUsers = array();

		$arFilter = Array("GROUPS_ID"=>array(CExtranet::GetExtranetUserGroupID()));

		$rsUsers = CUser::GetList(($by="ID"), ($order="asc"), $arFilter);
		while($arUser = $rsUsers->GetNext())
		{
			if ($full)
				$arExtranetGroupUsers[] = $arUser;
			else
				$arExtranetGroupUsers[] = $arUser["ID"];
		}

		return $arExtranetGroupUsers;
	}

	public static function GetPublicUsers($full = false)
	{
		global $USER;

		$arPublicUsers = array();
		$arFilter = Array(
			COption::GetOptionString("extranet", "extranet_public_uf_code", "UF_PUBLIC") => "1", 
			"ID" => "~".$USER->GetID(), 
			"!UF_DEPARTMENT" => false, 
			"GROUPS_ID" => array(CExtranet::GetExtranetUserGroupID())
		);

		$rsUsers = CUser::GetList(($by="ID"), ($order="asc"), $arFilter);
		while($arUser = $rsUsers->GetNext())
		{
			if ($full)
				$arPublicUsers[] = $arUser;
			else
				$arPublicUsers[] = $arUser["ID"];
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
					($by="ID"), 
					($order="asc"), 
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

	public static function IsProfileViewable($arUser, $site_id = false, $bOnlyActive = true, $arContext = array())
	{
		global $USER;

		if (
			isset($arUser['EXTERNAL_AUTH_ID'])
		)
		{
			if ($arUser['EXTERNAL_AUTH_ID'] == 'replica')
			{
				return true;
			}
			elseif ($arUser['EXTERNAL_AUTH_ID'] == 'email')
			{
				return false;
			}
		}

		// if current user is admin
		if (CExtranet::IsExtranetAdmin())
		{
			return true;
		}

		// if extranet site is not set
		if (!CExtranet::GetExtranetSiteID())
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
			!CExtranet::IsExtranetSite($site_id)
			&& !CExtranet::IsIntranetUser()
		)
		{
			return false;
		}

		$bNeedCheckContext = false;

		// if intranet and profile user is not employee
		if (!CExtranet::IsExtranetSite($site_id))
		{
			if (
				CExtranet::IsIntranetUser()
				&& (
					(
						!is_array($arUser["UF_DEPARTMENT"])
						&& intval($arUser["UF_DEPARTMENT"]) > 0
					)
					|| (
						is_array($arUser["UF_DEPARTMENT"])
						&& intval($arUser["UF_DEPARTMENT"][0]) > 0
					)
				)
			)
			{
				return true;
			}
			else
			{
				$arUsersInMyGroupsID = CExtranet::GetMyGroupsUsers(CExtranet::GetExtranetSiteID(), false, $bOnlyActive);
				if (
					!in_array($arUser["ID"], $arUsersInMyGroupsID)
					&& ($arUser["ID"] != $USER->GetID())
				)
				{
					$bNeedCheckContext = true;
				}
			}
		}

		// if extranet and profile user not public
		if (
			CExtranet::IsExtranetSite($site_id)
			&& $arUser[COption::GetOptionString("extranet", "extranet_public_uf_code", "UF_PUBLIC")] != 1
		)
		{
			$arUsersInMyGroupsID = CExtranet::GetMyGroupsUsers(SITE_ID);
			if (
				!in_array($arUser["ID"], $arUsersInMyGroupsID)
				&& ($arUser["ID"] != $USER->GetID())
			)
			{
				$bNeedCheckContext = true;
			}
		}

		if ($bNeedCheckContext)
		{
			if (
				isset($arContext)
				&& isset($arContext["ENTITY_TYPE"])
				&& in_array($arContext["ENTITY_TYPE"], array("LOG_ENTRY"))
				&& isset($arContext["ENTITY_ID"])
				&& intval($arContext["ENTITY_ID"]) > 0
			)
			{
				return CSocNetUser::CheckContext($USER->GetID(), $arUser["ID"], array_merge($arContext, array('SITE_ID' => CExtranet::GetExtranetSiteID())));
			}
			else
			{
				return false;
			}
		}

		return true;
	}

	public static function IsProfileViewableByID($user_id, $site_id = false)
	{
		if (
			CExtranet::IsExtranetAdmin()
			||
			(
				IsModuleInstalled("bitrix24")
				&& CSocNetUser::IsCurrentUserModuleAdmin(SITE_ID, false)
			)
		)
			return true;

		if (IntVal($user_id) > 0 && strlen(CExtranet::GetExtranetSiteID()) > 0)
		{
			$dbUser = CUser::GetByID($user_id);
			$arUser = $dbUser->Fetch();

			if (!CExtranet::IsProfileViewable($arUser, $site_id))
				return false;
		}
		return true;
	}

	public static function ModifyGroupDefaultFeatures(&$arSocNetFeaturesSettings, $site_id = false)
	{
		if (CExtranet::IsExtranetSite($site_id))
		{
			$arSocNetFeaturesSettings["calendar"]["operations"]["write"][SONET_ENTITY_GROUP] = SONET_ROLES_USER;
			$arSocNetFeaturesSettings["files"]["operations"]["write_limited"][SONET_ENTITY_GROUP] = SONET_ROLES_USER;
			$arSocNetFeaturesSettings["blog"]["operations"]["write_post"][SONET_ENTITY_GROUP] = SONET_ROLES_USER;
		}
	}


	public static function OnBeforeSocNetGroupUpdateHandler($ID, $arFields)
	{
		global $bArchiveBeforeUpdate, $APPLICATION;

		if (!array_key_exists("CLOSED", $arFields))
			return true;

		if (!CModule::IncludeModule("socialnetwork"))
			return false;

		$arSocNetGroup = CSocNetGroup::GetByID($ID);
		if (!$arSocNetGroup)
		{
			$APPLICATION->ThrowException(GetMessage("SONET_NO_GROUP"), "ERROR_NO_GROUP");
			return false;
		}
		else
		{
			if (CModule::IncludeModule('extranet'))
			{
				$ExtranetSiteID = CExtranet::GetExtranetSiteID();
				$arGroupSites = array();

				$rsGroupSite = CSocNetGroup::GetSite($ID);
				while($arGroupSite = $rsGroupSite->Fetch())
				{
					$arGroupSites[] = $arGroupSite["LID"];
				}

				if (!in_array($ExtranetSiteID, $arGroupSites))
					return true;
			}
			else
			{
				return true;
			}

			$bArchiveBeforeUpdate = ($arSocNetGroup["CLOSED"] == "Y");

			return true;
		}
	}

	public static function OnSocNetGroupUpdateHandler($ID, $arFields)
	{
		global $bArchiveBeforeUpdate, $APPLICATION;

		if (!array_key_exists("CLOSED", $arFields))
			return true;

		if (intval($ID) <= 0)
			return false;

		if (!CModule::IncludeModule('socialnetwork'))
			return false;

		if (CModule::IncludeModule('extranet'))
		{
			$arSocNetGroup = CSocNetGroup::GetByID($ID);
			if (!$arSocNetGroup)
			{
				$APPLICATION->ThrowException(GetMessage("SONET_NO_GROUP"), "ERROR_NO_GROUP");
				return false;
			}
			else
			{
				$ExtranetSiteID = CExtranet::GetExtranetSiteID();
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
		}
		else
		{
			return true;
		}

		$bFromArchiveToOpen = $bFromOpenToArchive = false;
		if ($arFields["CLOSED"] == "Y" && !$bArchiveBeforeUpdate)
		{
			$bFromOpenToArchive = true;
		}
		elseif ($arFields["CLOSED"] != "Y" && $bArchiveBeforeUpdate)
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
	public static function OnSocNetUserToGroupAdd($ID, $arFields)
	{
		if(!defined("BX_COMP_MANAGED_CACHE"))
		{
			return true;
		}

		global $CACHE_MANAGER;

		if (
			array_key_exists("ROLE", $arFields)
			&& array_key_exists("GROUP_ID", $arFields)
			&& intval($arFields["GROUP_ID"]) > 0
			&& intval($arFields["USER_ID"]) > 0
		)
		{
			if (!CModule::IncludeModule('socialnetwork'))
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

			if ($dbUsersInGroup)
			{
				while ($arUserInGroup = $dbUsersInGroup->GetNext())
				{
					$CACHE_MANAGER->ClearByTag("extranet_user_".$arUserInGroup["USER_ID"]);
				}
			}

			$CACHE_MANAGER->ClearByTag("extranet_user_".$arFields["USER_ID"]);
		}

		return true;
	}

	/*
	RegisterModuleDependences('socialnetwork', 'OnSocNetUserToGroupUpdate', 'extranet', 'CExtranet', 'OnSocNetUserToGroupUpdate');
	*/
	public static function OnSocNetUserToGroupUpdate($ID, $arFields)
	{
		if(!defined("BX_COMP_MANAGED_CACHE"))
			return true;

		global $CACHE_MANAGER;

		if (
			array_key_exists("ROLE", $arFields)
			&& array_key_exists("GROUP_ID", $arFields)
			&& intval($arFields["GROUP_ID"]) > 0
			&& intval($arFields["USER_ID"]) > 0
		)
		{
			if (!CModule::IncludeModule('socialnetwork'))
				return false;

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

			if ($dbUsersInGroup)
			{
				while ($arUserInGroup = $dbUsersInGroup->GetNext())
					$CACHE_MANAGER->ClearByTag("extranet_user_".$arUserInGroup["USER_ID"]);
			}

			$CACHE_MANAGER->ClearByTag("extranet_user_".$arFields["USER_ID"]);
		}

		return true;
	}

	/*
	RegisterModuleDependences('socialnetwork', 'OnSocNetUserToGroupDelete', 'extranet', 'CExtranet', 'OnSocNetUserToGroupDelete');
	*/
	public static function OnSocNetUserToGroupDelete($ID)
	{
		if(!defined("BX_COMP_MANAGED_CACHE"))
			return true;

		if (!CModule::IncludeModule('socialnetwork'))
			return false;

		global $CACHE_MANAGER;

		$arUser2Group = CSocNetUserToGroup::GetByID($ID);
		if (!$arUser2Group)
			return true;

		if (
			array_key_exists("GROUP_ID", $arUser2Group)
			&& array_key_exists("USER_ID", $arUser2Group)
			&& intval($arUser2Group["GROUP_ID"]) > 0
			&& intval($arUser2Group["USER_ID"]) > 0
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

			if ($dbUsersInGroup)
			{
				while ($arUserInGroup = $dbUsersInGroup->GetNext())
					$CACHE_MANAGER->ClearByTag("extranet_user_".$arUserInGroup["USER_ID"]);
			}

			$CACHE_MANAGER->ClearByTag("extranet_user_".$arUser2Group["USER_ID"]);
		}

		return true;
	}

	/*
	RegisterModuleDependences('main', 'OnUserDelete', 'extranet', 'CExtranet', 'OnUserDelete', 10);
	*/
	public static function OnUserDelete($ID)
	{
		if(!defined("BX_COMP_MANAGED_CACHE"))
			return true;

		global $CACHE_MANAGER;

		if (intval($ID) > 0)
		{
			if (!CModule::IncludeModule('socialnetwork'))
				return false;

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

			$arUserSocNetGroups = array();

			if ($dbUsersInGroup)
			{
				while ($arUserInGroup = $dbUsersInGroup->GetNext())
				{
					$arUserSocNetGroups[] = $arUserInGroup["GROUP_ID"];
				}
			}

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
						$CACHE_MANAGER->ClearByTag("extranet_user_".$arUserInGroup["USER_ID"]);
					}
				}
			}

			$CACHE_MANAGER->ClearByTag("extranet_user_".$ID);
		}

		return true;
	}

	/*
	RegisterModuleDependences('socialnetwork', 'OnSocNetGroupDelete', 'extranet', 'CExtranet', 'OnSocNetGroupDelete');
	*/
	public static function OnSocNetGroupDelete($ID)
	{
		if(!defined("BX_COMP_MANAGED_CACHE"))
			return true;

		global $CACHE_MANAGER;

		if (intval($ID) > 0)
		{
			if (!CModule::IncludeModule('socialnetwork'))
				return false;

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

			if ($dbUsersInGroup)
				while ($arUserInGroup = $dbUsersInGroup->GetNext())
					$CACHE_MANAGER->ClearByTag("extranet_user_".$arUserInGroup["USER_ID"]);
		}

		return true;
	}

	/*
	RegisterModuleDependences('main', 'onBeforeUserAdd', 'extranet', 'CExtranet', 'ClearPublicUserCacheOnAddUpdate');
	RegisterModuleDependences('main', 'onBeforeUserUpdate', 'extranet', 'CExtranet', 'ClearPublicUserCacheOnAddUpdate');
	*/
	public static function ClearPublicUserCacheOnAddUpdate($arFields)
	{
		global $CACHE_MANAGER;

		if (intval($arFields["ID"]) > 0) // update
		{
			$dbRes = CUser::GetList(
				$by="id", $order="asc",
				array("ID_EQUAL_EXACT" => intval($arFields['ID'])),
				array('SELECT' => array('UF_PUBLIC'))
			);

			if ($arOldFields = $dbRes->Fetch())
			{
				if (
					isset($arFields['UF_PUBLIC'])
					&& $arOldFields['UF_PUBLIC'] != $arFields['UF_PUBLIC']
				)
					$CACHE_MANAGER->ClearByTag("extranet_public");
			}
		}
		else // add
		{
			if (isset($arFields['UF_PUBLIC']))
				$CACHE_MANAGER->ClearByTag("extranet_public");
		}

		return true;
	}

	/*
	RegisterModuleDependences('main', 'OnUserDelete', 'extranet', 'CExtranet', 'ClearPublicUserCacheOnDelete');
	*/
	public static function ClearPublicUserCacheOnDelete($ID)
	{
		global $CACHE_MANAGER;

		if (intval($ID) > 0)
		{
			$dbRes = CUser::GetList(
				$by="id", $order="asc",
				array("ID_EQUAL_EXACT" => intval($ID)),
				array('SELECT' => array('UF_PUBLIC'))
			);

			if ($arFields = $dbRes->Fetch())
			{
				if (
					array_key_exists("UF_PUBLIC", $arFields)
					&& $arFields["UF_PUBLIC"]
				)
				{
					$CACHE_MANAGER->ClearByTag("extranet_public");
				}
			}
		}

		return true;
	}

	public static function GetSitesByLogDestinations($arRights, $authorId = false, $explicit_site_id = false)
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
			$extranet_site_id = CExtranet::GetExtranetSiteID();
			$arIntranetSiteID = array();
			$rsSite = CSite::GetList(
				$by="sort", 
				$order="desc", 
				array("ACTIVE" => "Y")
			);
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
			$arFilter = array(
				"UF_DEPARTMENT" => false,
				"ID" => implode('|', $arUserId)
			);

			$arExternalAuthId = array();
			if (IsModuleInstalled('mail'))
			{
				$arExternalAuthId[] = 'email';
			}

			if (IsModuleInstalled('replica'))
			{
				$arExternalAuthId[] = 'replica';
			}

			if (IsModuleInstalled('im'))
			{
				$arExternalAuthId[] = 'bot';
			}

			if (IsModuleInstalled('imconnector'))
			{
				$arExternalAuthId[] = 'imconnector';
			}

			if (!empty($arExternalAuthId))
			{
				$arFilter['!EXTERNAL_AUTH_ID'] = $arExternalAuthId;
			}

			$rsUser = CUser::GetList(
				($by = ''), ($order = ''),
				$arFilter,
				array("FIELDS" => array("ID"))
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
			&& CModule::IncludeModule('socialnetwork')
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

		$currentSiteId = ($explicit_site_id ? $explicit_site_id : SITE_ID);
		if (
			in_array($currentSiteId, $arIntranetSiteID)
			&& !in_array($currentSiteId, $arSiteID)
		)
		{
			$arSiteID[] = $currentSiteId;
		}

		return array_unique($arSiteID);
	}

	public static function WorkgroupsAllowed()
	{
		return (COption::GetOptionString('extranet', 'allow_groups', 'Y') == 'Y');
	}

	public static function ShowAllContactsAllowed()
	{
		return (COption::GetOptionString('extranet', 'show_all_contacts', 'N') == 'Y');
	}

	public static function fillUserListFilterORM($arParams = array(), &$arFilter = array(), &$bFilteredByUserId)
	{
		global $USER;

		static $IsShowAllContacts = false;
		static $IsExtranetWorkGroupsAllowed = false;

		if (!is_array($arParams))
		{
			$arParams = array();
		}

		$currentUserId = (
			!isset($arParams["CURRENT_USER_ID"])
				? intval($USER->GetId())
				: intval($arParams["CURRENT_USER_ID"])
		);

		$bExtranetUser = (
			!isset($arParams["EXTRANET_USER"])
				? !CExtranet::IsIntranetUser(SITE_ID, $currentUserId)
				: !!($arParams["EXTRANET_USER"])
		);

		$arFilteredUserIDs = (
			isset($arParams["MY_USERS"])
			&& is_array($arParams["MY_USERS"])
				? $arParams["MY_USERS"]
				: array()
		);

		$bEmailUsersAll = (
			isset($arParams["EMAIL_USERS_ALL"])
				? $arParams["EMAIL_USERS_ALL"]
				: (IsModuleInstalled('mail') && \Bitrix\Main\Config\Option::get('socialnetwork', 'email_users_all', 'N') == 'Y')
		);

		if ($IsShowAllContacts === false)
		{
			$IsShowAllContacts = (
				CExtranet::ShowAllContactsAllowed()
					? "Y"
					: "N"
			);
		}

		if ($IsExtranetWorkGroupsAllowed === false)
		{
			$IsExtranetWorkGroupsAllowed = (
				CExtranet::WorkgroupsAllowed()
					? "Y"
					: "N"
			);
		}

		if (
			$IsExtranetWorkGroupsAllowed == "Y"
			&& (
				$bExtranetUser
				|| $IsShowAllContacts != "Y"
			)
		)
		{
			$arFilteredUserIDs = array_merge($arFilteredUserIDs, CExtranet::GetMyGroupsUsers(CExtranet::GetExtranetSiteID()));
		}

		if ($bExtranetUser)
		{
			if ($IsExtranetWorkGroupsAllowed != "Y")
			{
				$arFilter = false;
			}
			else
			{
				$arFilter["ID"] = array_unique(array_merge(array($currentUserId), $arFilteredUserIDs));
				$bFilteredByUserId = true;
			}
		}
		elseif ($IsShowAllContacts != "Y")
		{
			if ($IsExtranetWorkGroupsAllowed != "Y")
			{
				$arFilter["!UF_DEPARTMENT"] = false;
			}
			elseif ($bEmailUsersAll)
			{
				$arFilter[] = array(
					'LOGIC' => 'OR',
					'!UF_DEPARTMENT' => false,
					'=EXTERNAL_AUTH_ID' => 'email',
					'ID' => array_unique(array_merge(array($currentUserId), $arFilteredUserIDs))
				);
				$bFilteredByUserId = true;
			}
			else
			{
				$arFilter[] = array(
					'LOGIC' => 'OR',
					'!UF_DEPARTMENT' => false,
					'ID' => array_unique(array_merge(array($currentUserId), $arFilteredUserIDs))
				);
				$bFilteredByUserId = true;
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

	public static function OnGetProfileView($currentUserId, $arUser, $siteId, $arContext, $bOnlyActive = true)
	{
		global $USER;

		if ($currentUserId == $USER->GetId())
		{
			return self::IsProfileViewable($arUser, $siteId, $bOnlyActive, $arContext);
		}
		else
		{
			return false;
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

		if (strlen($site) < 0)
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

		$userId = intval($userId);

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

	function set($site, $bGadget = false, $arValue = array())
	{
		global $USER;

		if (strlen($site) <= 0)
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

	function setForKey($key, $arValue = array())
	{
		if (strlen($key) <= 0)
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
