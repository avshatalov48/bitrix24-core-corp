<?
IncludeModuleLangFile(__FILE__);

use Bitrix\Socialnetwork\UserToGroupTable;
use Bitrix\Socialnetwork\Item\UserToGroup;
use Bitrix\Socialnetwork\Integration;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

class CAllSocNetUserToGroup
{
	protected static $roleCache = array();

	/***************************************/
	/********  DATA MODIFICATION  **********/
	/***************************************/
	function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		global $APPLICATION, $DB, $arSocNetAllowedRolesForUserInGroup, $arSocNetAllowedInitiatedByType;

		if ($ACTION != "ADD" && intval($ID) <= 0)
		{
			$APPLICATION->ThrowException("System error 870164", "ERROR");
			return false;
		}

		if ((is_set($arFields, "USER_ID") || $ACTION=="ADD") && intval($arFields["USER_ID"]) <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UG_EMPTY_USER_ID"), "EMPTY_USER_ID");
			return false;
		}
		elseif (is_set($arFields, "USER_ID"))
		{
			$dbResult = CUser::GetByID($arFields["USER_ID"]);
			if (!$dbResult->Fetch())
			{
				$APPLICATION->ThrowException(GetMessage("SONET_UG_ERROR_NO_USER_ID"), "ERROR_NO_USER_ID");
				return false;
			}
		}

		if ((is_set($arFields, "GROUP_ID") || $ACTION=="ADD") && intval($arFields["GROUP_ID"]) <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UG_EMPTY_GROUP_ID"), "EMPTY_GROUP_ID");
			return false;
		}
		elseif (is_set($arFields, "GROUP_ID"))
		{
			$arResult = CSocNetGroup::GetByID($arFields["GROUP_ID"]);
			if ($arResult == false)
			{
				$APPLICATION->ThrowException(GetMessage("SONET_UG_ERROR_NO_GROUP_ID"), "ERROR_NO_GROUP_ID");
				return false;
			}
		}

		if ((is_set($arFields, "ROLE") || $ACTION=="ADD") && $arFields["ROLE"] == '')
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UG_EMPTY_ROLE"), "EMPTY_ROLE");
			return false;
		}
		elseif (is_set($arFields, "ROLE") && !in_array($arFields["ROLE"], $arSocNetAllowedRolesForUserInGroup))
		{
			$APPLICATION->ThrowException(str_replace("#ID#", $arFields["ROLE"], GetMessage("SONET_UG_ERROR_NO_ROLE")), "ERROR_NO_ROLE");
			return false;
		}

		if ((is_set($arFields, "INITIATED_BY_TYPE") || $ACTION=="ADD") && $arFields["INITIATED_BY_TYPE"] == '')
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UG_EMPTY_INITIATED_BY_TYPE"), "EMPTY_INITIATED_BY_TYPE");
			return false;
		}
		elseif (is_set($arFields, "INITIATED_BY_TYPE") && !in_array($arFields["INITIATED_BY_TYPE"], $arSocNetAllowedInitiatedByType))
		{
			$APPLICATION->ThrowException(str_replace("#ID#", $arFields["INITIATED_BY_TYPE"], GetMessage("SONET_UG_ERROR_NO_INITIATED_BY_TYPE")), "ERROR_NO_INITIATED_BY_TYPE");
			return false;
		}

		if ((is_set($arFields, "INITIATED_BY_USER_ID") || $ACTION=="ADD") && intval($arFields["INITIATED_BY_USER_ID"]) <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UG_EMPTY_INITIATED_BY_USER_ID"), "EMPTY_INITIATED_BY_USER_ID");
			return false;
		}
		elseif (is_set($arFields, "INITIATED_BY_USER_ID"))
		{
			$dbResult = CUser::GetByID($arFields["INITIATED_BY_USER_ID"]);
			if (!$dbResult->Fetch())
			{
				$APPLICATION->ThrowException(GetMessage("SONET_UG_ERROR_NO_INITIATED_BY_USER_ID"), "ERROR_NO_INITIATED_BY_USER_ID");
				return false;
			}
		}

		if (is_set($arFields, "DATE_CREATE") && (!$DB->IsDate($arFields["DATE_CREATE"], false, LANG, "FULL")))
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UG_EMPTY_DATE_CREATE"), "EMPTY_DATE_CREATE");
			return false;
		}

		if (is_set($arFields, "DATE_UPDATE") && (!$DB->IsDate($arFields["DATE_UPDATE"], false, LANG, "FULL")))
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UG_EMPTY_DATE_UPDATE"), "EMPTY_DATE_UPDATE");
			return false;
		}

		if (is_set($arFields, "DATE_LAST_VIEW") && (!$DB->IsDate($arFields["DATE_LAST_VIEW"], false, LANG, "FULL")))
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UG_EMPTY_DATE_LAST_VIEW"), "EMPTY_DATE_LAST_VIEW");
			return false;
		}

		if ((is_set($arFields, "SEND_MAIL") && $arFields["SEND_MAIL"] != "N") || !is_set($arFields, "SEND_MAIL"))
			$arFields["SEND_MAIL"] = "Y";

		return True;
	}

	public static function Delete($ID, $bSendExclude = false)
	{
		global $APPLICATION, $DB, $USER, $CACHE_MANAGER;

		if (!CSocNetGroup::__ValidateID($ID))
			return false;

		$ID = intval($ID);

		$arUser2Group = CSocNetUserToGroup::GetByID($ID);
		if (!$arUser2Group)
		{
			$APPLICATION->ThrowException(GetMessage("SONET_NO_USER2GROUP"), "ERROR_NO_USER2GROUP");
			return false;
		}

		$db_events = GetModuleEvents("socialnetwork", "OnBeforeSocNetUserToGroupDelete");
		while ($arEvent = $db_events->Fetch())
		{
			if (ExecuteModuleEventEx($arEvent, array($ID)) === false)
			{
				return false;
			}
		}

		$events = GetModuleEvents("socialnetwork", "OnSocNetUserToGroupDelete");
		while ($arEvent = $events->Fetch())
		{
			ExecuteModuleEventEx($arEvent, array($ID, $arUser2Group));
		}

		if (CModule::IncludeModule("im"))
		{
			CIMNotify::DeleteByTag("SOCNET|INVITE_GROUP|".intval($arUser2Group['USER_ID'])."|".intval($ID));
		}

		$bSuccess = $DB->Query("DELETE FROM b_sonet_user2group WHERE ID = ".$ID."", true);

		CSocNetGroup::SetStat($arUser2Group["GROUP_ID"]);
		CSocNetSearch::OnUserRelationsChange($arUser2Group["USER_ID"]);

		if (array_key_exists($arUser2Group["USER_ID"]."_".$arUser2Group["GROUP_ID"], self::$roleCache))
		{
			unset(self::$roleCache[$arUser2Group["USER_ID"]."_".$arUser2Group["GROUP_ID"]]);
		}

		if($bSuccess && defined("BX_COMP_MANAGED_CACHE"))
		{
			$CACHE_MANAGER->ClearByTag("sonet_user2group_G".$arUser2Group["GROUP_ID"]);
			$CACHE_MANAGER->ClearByTag("sonet_user2group_U".$arUser2Group["USER_ID"]);
			$CACHE_MANAGER->ClearByTag("sonet_user2group");
		}

		if (
			$bSuccess 
			&& in_array($arUser2Group["ROLE"], array(SONET_ROLES_MODERATOR, SONET_ROLES_USER))
		)
		{
			$chatNotificationResult = false;

			if (CModule::IncludeModule("im"))
			{
				$chatNotificationResult = UserToGroup::addInfoToChat(array(
					'group_id' => $arUser2Group["GROUP_ID"],
					'user_id' => $arUser2Group["USER_ID"],
					'action' => UserToGroup::CHAT_ACTION_OUT,
					'sendMessage' => $bSendExclude
				));

				if ($bSendExclude)
				{
					$arMessageFields = array(
						"TO_USER_ID" => $arUser2Group["USER_ID"],
						"FROM_USER_ID" => 0,
						"NOTIFY_TYPE" => IM_NOTIFY_SYSTEM,
						"NOTIFY_MODULE" => "socialnetwork",
						"NOTIFY_EVENT" => "invite_group",
						"NOTIFY_TAG" => "SOCNET|INVITE_GROUP|".intval($arUser2Group["USER_ID"])."|".intval($arUser2Group["ID"]),
						"NOTIFY_MESSAGE" => str_replace(array("#NAME#"), array($arUser2Group["GROUP_NAME"]), GetMessage("SONET_UG_EXCLUDE_MESSAGE"))
					);

					CIMNotify::Add($arMessageFields);
				}
			}

			if (
				$bSendExclude
				&& !$chatNotificationResult
			)
			{
				CSocNetUserToGroup::notifyImToModerators(array(
					"TYPE" => "exclude",
					"RELATION_ID" => $arUser2Group["ID"],
					"USER_ID" => $arUser2Group["USER_ID"],
					"GROUP_ID" => $arUser2Group["GROUP_ID"],
					"GROUP_NAME" => $arUser2Group["GROUP_NAME"],
					"EXCLUDE_USERS" => array($USER->GetID())
				));
			}
		}

		return $bSuccess;
	}

	public static function DeleteNoDemand($userID)
	{
		global $DB;

		if (!CSocNetGroup::__ValidateID($userID))
			return false;

		$userID = intval($userID);
		$bSuccess = True;

		$arGroups = array();

		$dbResult = CSocNetUserToGroup::GetList(array(), array("USER_ID" => $userID), false, false, array("GROUP_ID"));
		while ($arResult = $dbResult->Fetch())
			$arGroups[] = $arResult["GROUP_ID"];

		$DB->Query("DELETE FROM b_sonet_user2group WHERE USER_ID = ".$userID."", true);

		$tmp_count = count($arGroups);
		for ($i = 0; $i < $tmp_count; $i++)
			CSocNetGroup::SetStat($arGroups[$i]);

		self::$roleCache = array();

		CSocNetUserToGroup::__SpeedFileDelete($userID);
		CSocNetSearch::OnUserRelationsChange($userID);

		return $bSuccess;
	}

	/***************************************/
	/**********  DATA SELECTION  ***********/
	/***************************************/
	public static function GetByID($ID)
	{
		if (!CSocNetGroup::__ValidateID($ID))
			return false;

		$ID = intval($ID);

		$dbResult = CSocNetUserToGroup::GetList(
			Array(),
			Array("ID" => $ID),
			false,
			false,
			Array("ID", "USER_ID", "GROUP_ID", "GROUP_VISIBLE", "GROUP_NAME", "ROLE", "DATE_CREATE", "DATE_UPDATE", "INITIATED_BY_TYPE", "INITIATED_BY_USER_ID", "MESSAGE")
		);
		if ($arResult = $dbResult->GetNext())
			return $arResult;

		return False;
	}

	/***************************************/
	/**********  COMMON METHODS  ***********/
	/***************************************/
	public static function GetUserRole($userID, $groupID, $bExtendedReturn = false)
	{
		$userID = intval($userID);
		if ($userID <= 0)
		{
			return false;
		}

		// compatibility?
		if (array_key_exists("arSocNetUserInRoleCache", $_REQUEST))
		{
			self::$roleCache = array();
		}

		if (is_array($groupID))
		{
			$arReturn = false;

			$arGroupToGet = array();
			foreach($groupID as $TmpGroupID)
			{
				if (!array_key_exists($userID."_".$TmpGroupID, self::$roleCache))
				{
					$arGroupToGet[] = $TmpGroupID;
				}
			}

			if (count($arGroupToGet) > 0)
			{
				$dbResult = CSocNetUserToGroup::GetList(
					array(),
					array("USER_ID" => $userID, "GROUP_ID" => $arGroupToGet),
					false,
					false,
					array("GROUP_ID", "ROLE", "AUTO_MEMBER")
				);
				$arRolesFromDB = array();
				while ($arResult = $dbResult->Fetch())
				{
					$arRolesFromDB[$arResult["GROUP_ID"]] = array(
						"ROLE" => $arResult["ROLE"],
						"AUTO_MEMBER" => $arResult["AUTO_MEMBER"]
					);
				}

				foreach($arGroupToGet as $TmpGroupID)
				{
					self::$roleCache[$userID."_".$TmpGroupID] = (
						array_key_exists($TmpGroupID, $arRolesFromDB)
							? array(
								"ROLE" => $arRolesFromDB[$TmpGroupID]["ROLE"],
								"AUTO_MEMBER" => $arRolesFromDB[$TmpGroupID]["AUTO_MEMBER"]
							)
							: array(
								"ROLE" => false,
								"AUTO_MEMBER" => "N"
							)
					);
				}
			}

			foreach($groupID as $TmpGroupID)
			{
				if ($arReturn === false)
				{
					$arReturn = array();
				}
				$arReturn[$TmpGroupID] = (
					$bExtendedReturn
						? self::$roleCache[$userID."_".$TmpGroupID]
						: self::$roleCache[$userID."_".$TmpGroupID]["ROLE"]
				);
			}

			return $arReturn;
		}
		else
		{
			$groupID = intval($groupID);
			if ($groupID <= 0)
			{
				return false;
			}

			if (!array_key_exists($userID."_".$groupID, self::$roleCache))
			{
				$dbResult = CSocNetUserToGroup::GetList(
					array(),
					array("USER_ID" => $userID, "GROUP_ID" => $groupID),
					false,
					false,
					array("ROLE", "AUTO_MEMBER")
				);
				if ($arResult = $dbResult->Fetch())
				{
					self::$roleCache[$userID."_".$groupID] = array(
						"ROLE" => $arResult["ROLE"],
						"AUTO_MEMBER" => $arResult["AUTO_MEMBER"]
					);
				}
				else
				{
					self::$roleCache[$userID."_".$groupID] = array(
						"ROLE" => false,
						"AUTO_MEMBER" => false
					);
				}
			}

			return (
				$bExtendedReturn
					? self::$roleCache[$userID."_".$groupID]
					: self::$roleCache[$userID."_".$groupID]["ROLE"]
			);
		}
	}

	/***************************************/
	/**********  SEND EVENTS  **************/
	/***************************************/
	function SendEvent($userGroupID, $mailTemplate = "SONET_INVITE_GROUP")
	{
		$userGroupID = intval($userGroupID);
		if ($userGroupID <= 0)
			return false;

		$dbRelation = CSocNetUserToGroup::GetList(
			array(),
			array("ID" => $userGroupID),
			false,
			false,
			array("ID", "USER_ID", "GROUP_ID", "ROLE", "DATE_CREATE", "MESSAGE", "INITIATED_BY_TYPE", "INITIATED_BY_USER_ID", "GROUP_NAME", "USER_NAME", "USER_LAST_NAME", "USER_EMAIL", "USER_LID")
		);
		$arRelation = $dbRelation->Fetch();
		if (!$arRelation)
			return false;

		$arUserGroup = array();

		if (CModule::IncludeModule("extranet"))
			$arUserGroup = CUser::GetUserGroup($arRelation["USER_ID"]);

		$bExtranetInstalled = IsModuleInstalled("extranet");
		$siteID = false;

		$rsGroupSite = CSocNetGroup::GetSite($arRelation["GROUP_ID"]);
		while ($arGroupSite = $rsGroupSite->Fetch())
		{
			if ($bExtranetInstalled)
			{
				if (
					(
						CExtranet::IsExtranetSite($arGroupSite["LID"])
						&& in_array(CExtranet::GetExtranetUserGroupID(), $arUserGroup)
					)
					||
					(
						!CExtranet::IsExtranetSite($arGroupSite["LID"])
						&& !in_array(CExtranet::GetExtranetUserGroupID(), $arUserGroup)
					)
				)
				{
					$siteID = $arGroupSite["LID"];
					break;
				}
				else
					continue;
			}
			else
			{
				$siteID = $arGroupSite["LID"];
				break;
			}
		}

		if ($siteID == false || $siteID == '')
			return false;

		$requestsPagePath = str_replace("#USER_ID#", $arRelation["USER_ID"], COption::GetOptionString("socialnetwork", "user_request_page", 
			(IsModuleInstalled("intranet")) ? "/company/personal/user/#USER_ID#/requests/" : "/club/user/#USER_ID#/requests/", $siteID));

		$arUserInitiatedForEmail = array("NAME"=>"", "LAST_NAME"=>"");

		if (intval($arRelation["INITIATED_BY_USER_ID"]) > 0):

			$dbUserInitiated = CUser::GetList(
				($by="id"),
				($order="desc"),
				array("ID" => $arRelation["INITIATED_BY_USER_ID"])
			);

			if ($arUserInitiated = $dbUserInitiated->Fetch())
				$arUserInitiatedForEmail = array("NAME"=>$arUserInitiated["NAME"], "LAST_NAME"=>$arUserInitiated["LAST_NAME"]);

		endif;

		$arFields = array(
			"RELATION_ID" => $userGroupID,
			"URL" => $requestsPagePath,
			"GROUP_ID" => $arRelation["GROUP_ID"],
			"USER_ID" => $arRelation["USER_ID"],
			"GROUP_NAME" => $arRelation["GROUP_NAME"],
			"USER_NAME" => $arRelation["USER_NAME"],
			"USER_LAST_NAME" => $arRelation["USER_LAST_NAME"],
			"USER_EMAIL" => $arRelation["USER_EMAIL"],
			"INITIATED_USER_NAME" => $arUserInitiatedForEmail["NAME"],
			"INITIATED_USER_LAST_NAME" => $arUserInitiatedForEmail["LAST_NAME"],
			"MESSAGE" => $arRelation["MESSAGE"]
		);

		$event = new CEvent;
		$event->Send($mailTemplate, $siteID, $arFields, "N");

		return true;
	}

	/***************************************/
	/************  ACTIONS  ****************/
	/***************************************/
	public static function SendRequestToBeMember($userID, $groupID, $message, $RequestConfirmUrl = "", $bAutoSubscribe = true)
	{
		global $APPLICATION, $DB;

		$userID = intval($userID);
		if ($userID <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UR_EMPTY_USERID"), "ERROR_USERID");
			return false;
		}

		$groupID = intval($groupID);
		if ($groupID <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UR_EMPTY_GROUPID"), "ERROR_GROUPID");
			return false;
		}

		$arGroup = CSocNetGroup::GetByID($groupID);
		if (!$arGroup || !is_array($arGroup) || $arGroup["ACTIVE"] != "Y"/* || $arGroup["VISIBLE"] != "Y"*/)
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UG_ERROR_NO_GROUP_ID"), "ERROR_NO_GROUP");
			return false;
		}

		$arFields = array(
			"USER_ID" => $userID,
			"GROUP_ID" => $groupID,
			"ROLE" => SONET_ROLES_REQUEST,
			"=DATE_CREATE" => $DB->CurrentTimeFunction(),
			"=DATE_UPDATE" => $DB->CurrentTimeFunction(),
			"MESSAGE" => $message,
			"INITIATED_BY_TYPE" => SONET_INITIATED_BY_USER,
			"INITIATED_BY_USER_ID" => $userID
		);
		if ($arGroup["OPENED"] == "Y")
		{
			$arFields["ROLE"] = SONET_ROLES_USER;
		}

		$ID = CSocNetUserToGroup::Add($arFields);
		if (!$ID)
		{
			$errorMessage = "";
			if ($e = $APPLICATION->GetException())
			{
				$errorMessage = $e->GetString();
			}
			if ($errorMessage == '')
			{
				$errorMessage = GetMessage("SONET_UR_ERROR_CREATE_USER2GROUP");
			}

			$APPLICATION->ThrowException($errorMessage, "ERROR_CREATE_USER2GROUP");
			return false;
		}

		if ($arGroup["OPENED"] == "Y")
		{
			if ($bAutoSubscribe)
			{
				CSocNetLogEvents::AutoSubscribe($userID, SONET_ENTITY_GROUP, $groupID);
			}

			if (IsModuleInstalled("im"))
			{
				$chatNotificationResult = UserToGroup::addInfoToChat(array(
					'group_id' => $groupID,
					'user_id' => $userID,
					'action' => UserToGroup::CHAT_ACTION_IN,
					'role' => $arFields['ROLE']
				));

				if (!$chatNotificationResult)
				{
					CSocNetUserToGroup::notifyImToModerators(array(
						"TYPE" => "join",
						"RELATION_ID" => $ID,
						"USER_ID" => $userID,
						"GROUP_ID" => $groupID,
						"GROUP_NAME" => $arGroup["NAME"],
					));
				}
			}
		}
		elseif (
			trim($RequestConfirmUrl) <> ''
			&& CModule::IncludeModule("im")
		)
		{
			static $serverName;
			if (!$serverName)
			{
				$dbSite = CSite::GetByID(SITE_ID);
				$arSite = $dbSite->Fetch();
				$serverName = htmlspecialcharsEx($arSite["SERVER_NAME"]);
				if ($serverName == '')
				{
					if (defined("SITE_SERVER_NAME") && SITE_SERVER_NAME <> '')
					{
						$serverName = SITE_SERVER_NAME;
					}
					else
					{
						$serverName = COption::GetOptionString("main", "server_name", "");
					}
					if ($serverName == '')
					{
						$serverName = $_SERVER["SERVER_NAME"];
					}
				}
				$serverName = (CMain::IsHTTPS() ? "https" : "http")."://".$serverName;
			}

			// send sonet system messages to owner and (may be) moderators to accept or refuse request
			$FilterRole = ($arGroup["INITIATE_PERMS"] == SONET_ROLES_OWNER ? SONET_ROLES_OWNER : SONET_ROLES_MODERATOR);

			$dbRequests = CSocNetUserToGroup::GetList(
				array("USER_ID" => "ASC"),
				array(
					"GROUP_ID" => $groupID,
					"<=ROLE" => $FilterRole,
					"USER_ACTIVE" => "Y"
				),
				false,
				false,
				array("ID", "USER_ID", "USER_NAME", "USER_LAST_NAME", "USER_EMAIL")
			);
			if ($dbRequests)
			{
				$groupSiteId = CSocNetGroup::GetDefaultSiteId($groupID, $arGroup["SITE_ID"]);
				$workgroupsPage = COption::GetOptionString("socialnetwork", "workgroups_page", "/workgroups/", SITE_ID);
				$groupUrlTemplate = COption::GetOptionString("socialnetwork", "group_path_template", "/workgroups/group/#group_id#/", SITE_ID);
				$groupUrlTemplate = "#GROUPS_PATH#".mb_substr($groupUrlTemplate, mb_strlen($workgroupsPage), mb_strlen($groupUrlTemplate) - mb_strlen($workgroupsPage));
				$groupUrl = str_replace(array("#group_id#", "#GROUP_ID#"), $groupID, $groupUrlTemplate);

				while ($arRequests = $dbRequests->GetNext())
				{
					$arTmp = CSocNetLogTools::ProcessPath(
						array(
							"GROUP_URL" => $groupUrl
						), 
						$arRequests["USER_ID"],
						$groupSiteId
					);
					$groupUrl = $arTmp["URLS"]["GROUP_URL"];
					$domainName = (
					mb_strpos($groupUrl, "http://") === 0
						|| mb_strpos($groupUrl, "https://") === 0
							? ""
							: (
								isset($arTmp["DOMAIN"]) 
								&& !empty($arTmp["DOMAIN"]) 
									? "//".$arTmp["DOMAIN"]
									: ""
							)
					);

					$arMessageFields = array(
						"TO_USER_ID" => $arRequests["USER_ID"],
						"FROM_USER_ID" => $userID,
						"NOTIFY_TYPE" => IM_NOTIFY_CONFIRM,
						"NOTIFY_MODULE" => "socialnetwork",
						"NOTIFY_EVENT" => "invite_group_btn",
						"NOTIFY_TAG" => "SOCNET|REQUEST_GROUP|".intval($userID)."|".$groupID."|".intval($ID)."|".$arRequests["USER_ID"],
						"NOTIFY_SUB_TAG" => "SOCNET|REQUEST_GROUP|".intval($userID)."|".$groupID."|".intval($ID),
						"NOTIFY_TITLE" => str_replace(
							"#GROUP_NAME#",
							truncateText($arGroup["NAME"], 150),
							GetMessage("SONET_UG_REQUEST_CONFIRM_TEXT_EMPTY")
						),
						"NOTIFY_MESSAGE" => str_replace(
							array(
								"#TEXT#", 
								"#GROUP_NAME#"
							), 
							array(
								$message, 
								"<a href=\"".$domainName.$groupUrl."\" class=\"bx-notifier-item-action\">".$arGroup["NAME"]."</a>"
							),
							(empty($message)
								? GetMessage("SONET_UG_REQUEST_CONFIRM_TEXT_EMPTY")
								: GetMessage("SONET_UG_REQUEST_CONFIRM_TEXT")
							)
						),
						"NOTIFY_BUTTONS" => Array(
							Array("TITLE" => GetMessage("SONET_UG_REQUEST_CONFIRM"), "VALUE" => "Y", "TYPE" => "accept"),
							Array("TITLE" => GetMessage("SONET_UG_REQUEST_REJECT"), "VALUE" => "N", "TYPE" => "cancel"),
						),
					);

					$groupUrl = $serverName.str_replace("#group_id#", $groupID, COption::GetOptionString("socialnetwork", "group_path_template", "/workgroups/group/#group_id#/", SITE_ID));

					$arMessageFields["NOTIFY_MESSAGE_OUT"] = $arMessageFields["NOTIFY_MESSAGE"];
					$arMessageFields["NOTIFY_MESSAGE_OUT"] .= "\n\n".GetMessage("SONET_UG_GROUP_LINK").$groupUrl;
					$arMessageFields['NOTIFY_MESSAGE_OUT'] .= "\n\n".GetMessage("SONET_UG_REQUEST_CONFIRM_REJECT").": ".$RequestConfirmUrl;

					CIMNotify::Add($arMessageFields);
				}
			}
		}

		return true;
	}

	public static function SendRequestToJoinGroup($senderID, $userID, $groupID, $message, $bMail = true)
	{
		global $APPLICATION, $DB, $USER;

		$senderID = intval($senderID);
		if ($senderID <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UR_EMPTY_USERID"), "ERROR_SENDERID");
			return false;
		}

		$userID = intval($userID);
		if ($userID <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UR_EMPTY_USERID"), "ERROR_USERID");
			return false;
		}

		$groupID = intval($groupID);
		if ($groupID <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UR_EMPTY_GROUPID"), "ERROR_GROUPID");
			return false;
		}

		$arGroup = CSocNetGroup::GetByID($groupID);
		if (!$arGroup || !is_array($arGroup))
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UG_ERROR_NO_GROUP_ID"), "ERROR_NO_GROUP");
			return false;
		}

		$arGroupSites = array();
		$rsGroupSite = CSocNetGroup::GetSite($groupID);
		while ($arGroupSite = $rsGroupSite->Fetch())
		{
			$arGroupSites[] = $arGroupSite["LID"];
		}

		$userRole = CSocNetUserToGroup::GetUserRole($senderID, $groupID);
		$bUserIsMember = ($userRole && in_array($userRole, UserToGroupTable::getRolesMember()));
		$bCanInitiate = (
			$USER->IsAdmin()
			|| CSocNetUser::IsCurrentUserModuleAdmin($arGroupSites)
			|| (
				$userRole
				&& (
					(
						$arGroup["INITIATE_PERMS"] == SONET_ROLES_OWNER
						&& $senderID == $arGroup["OWNER_ID"]
					)
					|| (
						$arGroup["INITIATE_PERMS"] == SONET_ROLES_MODERATOR
						&& in_array($userRole, array(SONET_ROLES_OWNER, SONET_ROLES_MODERATOR))
					)
					|| (
						$arGroup["INITIATE_PERMS"] == SONET_ROLES_USER
						&& $bUserIsMember
					)
				)
			)
		);

		if (!$bCanInitiate)
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UG_ERROR_NO_PERMS"), "ERROR_NO_PERMS");
			return false;
		}

		$arFields = array(
			"USER_ID" => $userID,
			"GROUP_ID" => $groupID,
			"ROLE" => SONET_ROLES_REQUEST,
			"=DATE_CREATE" => $DB->CurrentTimeFunction(),
			"=DATE_UPDATE" => $DB->CurrentTimeFunction(),
			"MESSAGE" => str_replace(Array("#TEXT#", "#GROUP_NAME#"), Array($message, $arGroup["NAME"]), (empty($message) ? GetMessage("SONET_UG_INVITE_CONFIRM_TEXT_EMPTY") : GetMessage("SONET_UG_INVITE_CONFIRM_TEXT"))),
			"INITIATED_BY_TYPE" => SONET_INITIATED_BY_GROUP,
			"INITIATED_BY_USER_ID" => $senderID,
			"SEND_MAIL" => ($bMail ? "Y" : "N")
		);
		$ID = CSocNetUserToGroup::Add($arFields);
		if (!$ID)
		{
			$errorMessage = "";
			if ($e = $APPLICATION->GetException())
			{
				$errorMessage = $e->GetString();
			}

			if ($errorMessage == '')
			{
				$errorMessage = GetMessage("SONET_UR_ERROR_CREATE_USER2GROUP");
			}

			$APPLICATION->ThrowException($errorMessage, "ERROR_CREATE_USER2GROUP");
			return false;
		}

		$userIsConfirmed = true;
		$rsInvitedUser = CUser::GetByID($userID);
		$arInvitedUser = $rsInvitedUser->Fetch();

		if (
			(
				!is_array($arInvitedUser["UF_DEPARTMENT"])
				|| intval($arInvitedUser["UF_DEPARTMENT"][0]) <= 0
			) // extranet
			&& ($arInvitedUser["LAST_LOGIN"] <= 0)
			&& $arInvitedUser["LAST_ACTIVITY_DATE"] == ''
		)
		{
			$userIsConfirmed = false;
		}

		if (
			CModule::IncludeModule("im")
			&& $userIsConfirmed
		)
		{
			$arMessageFields = array(
				"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
				"TO_USER_ID" => intval($arFields['USER_ID']),
				"FROM_USER_ID" => intval($arFields['INITIATED_BY_USER_ID']),
				"NOTIFY_TYPE" => IM_NOTIFY_CONFIRM,
				"NOTIFY_MODULE" => "socialnetwork",
				"NOTIFY_EVENT" => "invite_group_btn",
				"NOTIFY_TAG" => "SOCNET|INVITE_GROUP|".intval($arFields['USER_ID'])."|".intval($ID),
				"NOTIFY_TITLE" => str_replace(
					"#GROUP_NAME#",
					truncateText($arGroup["NAME"], 150),
					GetMessage("SONET_UG_INVITE_CONFIRM_TEXT_EMPTY")
				),
				"NOTIFY_MESSAGE" => str_replace(Array("#TEXT#", "#GROUP_NAME#"), Array($message, $arGroup["NAME"]), (empty($message)?GetMessage("SONET_UG_INVITE_CONFIRM_TEXT_EMPTY"):GetMessage("SONET_UG_INVITE_CONFIRM_TEXT"))),
				"NOTIFY_BUTTONS" => Array(
					Array('TITLE' => GetMessage('SONET_UG_INVITE_CONFIRM'), 'VALUE' => 'Y', 'TYPE' => 'accept'),
					Array('TITLE' => GetMessage('SONET_UG_INVITE_REJECT'), 'VALUE' => 'N', 'TYPE' => 'cancel'),
				),
			);

			$siteId = (
				(
					!is_array($arInvitedUser["UF_DEPARTMENT"])
					|| intval($arInvitedUser["UF_DEPARTMENT"][0]) <= 0
				)
				&& CModule::IncludeModule('extranet')
					? CExtranet::GetExtranetSiteID()
					: SITE_ID
			);

			$dbSite = CSite::GetByID($siteId);
			$arSite = $dbSite->Fetch();
			$serverName = htmlspecialcharsEx($arSite["SERVER_NAME"]);

			if ($serverName == '')
			{
				$serverName = (defined("SITE_SERVER_NAME") && SITE_SERVER_NAME <> '' ? SITE_SERVER_NAME : COption::GetOptionString("main", "server_name", ""));
			}

			if ($serverName == '')
			{
				$serverName = $_SERVER["SERVER_NAME"];
			}

			$serverName = (CMain::IsHTTPS() ? "https" : "http")."://".$serverName;

			$requestUrl = COption::GetOptionString(
				"socialnetwork",
				"user_request_page",
				(IsModuleInstalled("intranet")) ? "/company/personal/user/#USER_ID#/requests/" : "/club/user/#USER_ID#/requests/",
				$siteId
			);

			$requestUrl = $serverName.str_replace(array("#USER_ID#", "#user_id#"), $userID, $requestUrl);
			$groupUrl = $serverName.str_replace("#group_id#", $groupID, COption::GetOptionString("socialnetwork", "group_path_template", "/workgroups/group/#group_id#/", $siteId));

			$arMessageFields['NOTIFY_MESSAGE_OUT'] = $arMessageFields['NOTIFY_MESSAGE'];
			$arMessageFields['NOTIFY_MESSAGE_OUT'] .= "\n\n".GetMessage('SONET_UG_GROUP_LINK').$groupUrl;
			$arMessageFields['NOTIFY_MESSAGE_OUT'] .= "\n\n".GetMessage('SONET_UG_INVITE_CONFIRM').": ".$requestUrl.'?INVITE_GROUP='.$ID.'&CONFIRM=Y';
			$arMessageFields['NOTIFY_MESSAGE_OUT'] .= "\n\n".GetMessage('SONET_UG_INVITE_REJECT').": ".$requestUrl.'?INVITE_GROUP='.$ID.'&CONFIRM=N';

			CIMNotify::Add($arMessageFields);
		}

		$events = GetModuleEvents("socialnetwork", "OnSocNetSendRequestToJoinGroup");
		while ($arEvent = $events->Fetch())
		{
			ExecuteModuleEventEx($arEvent, array($ID, $arFields));
		}

		CSocNetUserToGroup::__SpeedFileCreate($userID);

		return true;
	}

	public static function ConfirmRequestToBeMember($userID, $groupID, $arRelationID, $bAutoSubscribe = true) // request from a user confirmed by a moderator
	{
		global $APPLICATION, $DB, $USER;

		$userID = intval($userID);
		if ($userID <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UR_EMPTY_USERID"), "ERROR_USERID");
			return false;
		}

		$groupID = intval($groupID);
		if ($groupID <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UR_EMPTY_GROUPID"), "ERROR_GROUPID");
			return false;
		}

		if (!is_array($arRelationID))
		{
			return true;
		}

		$arGroup = CSocNetGroup::GetByID($groupID);
		if (!$arGroup || !is_array($arGroup))
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UG_ERROR_NO_GROUP_ID"), "ERROR_NO_GROUP");
			return false;
		}

		$arGroupSites = array();
		$rsGroupSite = CSocNetGroup::GetSite($groupID);
		while ($arGroupSite = $rsGroupSite->Fetch())
		{
			$arGroupSites[] = $arGroupSite["LID"];
		}

		$userRole = CSocNetUserToGroup::GetUserRole($userID, $groupID);
		$bUserIsMember = ($userRole && in_array($userRole, array(SONET_ROLES_OWNER, SONET_ROLES_MODERATOR, SONET_ROLES_USER)));
		$bCanInitiate = (
			$USER->IsAdmin()
			|| CSocNetUser::IsCurrentUserModuleAdmin($arGroupSites) 
			|| (
				$userRole
				&& (
					($arGroup["INITIATE_PERMS"] == SONET_ROLES_OWNER && $userID == $arGroup["OWNER_ID"])
					|| ($arGroup["INITIATE_PERMS"] == SONET_ROLES_MODERATOR && in_array($userRole, array(SONET_ROLES_OWNER, SONET_ROLES_MODERATOR)))
					|| ($arGroup["INITIATE_PERMS"] == SONET_ROLES_USER && $bUserIsMember)
				)
			)
		);

		if (!$bCanInitiate)
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UG_ERROR_NO_PERMS"), "ERROR_NO_PERMS");
			return false;
		}

		$bSuccess = true;
		$arSuccessRelations = array();
		$tmp_count = count($arRelationID);
		$chatNotificationResult = false;

		for ($i = 0; $i < $tmp_count; $i++)
		{
			$arRelationID[$i] = intval($arRelationID[$i]);
			if ($arRelationID[$i] <= 0)
			{
				continue;
			}

			$arRelation = CSocNetUserToGroup::GetByID($arRelationID[$i]);
			if (!$arRelation)
			{
				continue;
			}

			if (
				$arRelation["GROUP_ID"] != $groupID 
				|| $arRelation["INITIATED_BY_TYPE"] != SONET_INITIATED_BY_USER 
				|| $arRelation["ROLE"] != SONET_ROLES_REQUEST
			)
			{
				continue;
			}

			$arFields = array(
				"ROLE" => UserToGroupTable::ROLE_USER,
				"=DATE_UPDATE" => $DB->CurrentTimeFunction(),
			);
			if (CSocNetUserToGroup::Update($arRelation["ID"], $arFields))
			{
				$arSuccessRelations[] = $arRelation;

				if ($bAutoSubscribe)
				{
					CSocNetLogEvents::AutoSubscribe($arRelation["USER_ID"], SONET_ENTITY_GROUP, $groupID);
				}

				$chatNotificationResult = UserToGroup::addInfoToChat(array(
					'group_id' => $groupID,
					'user_id' => $arRelation["USER_ID"],
					'action' => UserToGroup::CHAT_ACTION_IN,
					'role' => $arFields['ROLE']
				));

				if (
					!$chatNotificationResult
					&& CModule::IncludeModule("im")
				)
				{
					$groupSiteId = CSocNetGroup::GetDefaultSiteId($groupID, $arGroup["SITE_ID"]);
					$workgroupsPage = COption::GetOptionString("socialnetwork", "workgroups_page", "/workgroups/", SITE_ID);
					$groupUrlTemplate = COption::GetOptionString("socialnetwork", "group_path_template", "/workgroups/group/#group_id#/", SITE_ID);
					$groupUrlTemplate = "#GROUPS_PATH#".mb_substr($groupUrlTemplate, mb_strlen($workgroupsPage), mb_strlen($groupUrlTemplate) - mb_strlen($workgroupsPage));
					$arTmp = CSocNetLogTools::ProcessPath(
						array(
							"GROUP_URL" => str_replace(array("#group_id#", "#GROUP_ID#"), $groupID, $groupUrlTemplate)
						),
						$arRelation["USER_ID"],
						$groupSiteId
					);
					$groupUrl = $arTmp["URLS"]["GROUP_URL"];

					$serverName = (
					mb_strpos($groupUrl, "http://") === 0
						|| mb_strpos($groupUrl, "https://") === 0
							? ""
							: $arTmp["SERVER_NAME"]
					);
					$domainName = (
					mb_strpos($groupUrl, "http://") === 0
						|| mb_strpos($groupUrl, "https://") === 0
							? ""
							: (
								isset($arTmp["DOMAIN"]) 
								&& !empty($arTmp["DOMAIN"]) 
									? "//".$arTmp["DOMAIN"]
									: ""
							)
					);

					$arMessageFields = array(
						"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
						"TO_USER_ID" => $arRelation["USER_ID"],
						"FROM_USER_ID" => $userID,
						"NOTIFY_TYPE" => IM_NOTIFY_FROM,
						"NOTIFY_MODULE" => "socialnetwork",
						"NOTIFY_EVENT" => "invite_group",
						"NOTIFY_TAG" => "SOCNET|INVITE_GROUP|".intval($arRelation["USER_ID"])."|".intval($arRelation["ID"]),
						"NOTIFY_MESSAGE" => str_replace(
							"#NAME#", 
							"<a href=\"".$domainName.$groupUrl."\" class=\"bx-notifier-item-action\">".$arGroup["NAME"]."</a>", 
							GetMessage("SONET_UG_CONFIRM_MEMBER_MESSAGE_G")
						),
						"NOTIFY_MESSAGE_OUT" => str_replace(
							"#NAME#", 
							$arGroup["NAME"], 
							GetMessage("SONET_UG_CONFIRM_MEMBER_MESSAGE_G")." (".$serverName.$groupUrl.")"
						)
					);

					CIMNotify::DeleteBySubTag("SOCNET|REQUEST_GROUP|".$arRelation["USER_ID"]."|".$arRelation["GROUP_ID"]."|".$arRelation["ID"]);
					CIMNotify::Add($arMessageFields);
				}
			}
			else
			{
				$errorMessage = "";
				if ($e = $APPLICATION->GetException())
				{
					$errorMessage = $e->GetString();
				}

				if ($errorMessage == '')
				{
					$errorMessage = GetMessage("SONET_UR_ERROR_CREATE_USER2GROUP");
				}

				$APPLICATION->ThrowException($errorMessage, "ERROR_CONFIRM_MEMBER");
				$bSuccess = false;
			}
		}

		if (
			!empty($arSuccessRelations)
			&& !$chatNotificationResult
		)
		{
			foreach ($arSuccessRelations as $arRel)
			{
				CSocNetUserToGroup::notifyImToModerators(array(
					"TYPE" => "join",
					"RELATION_ID" => $arRel["ID"],
					"USER_ID" => $arRel["USER_ID"],
					"GROUP_ID" => $arRel["GROUP_ID"],
					"GROUP_NAME" => $arRel["GROUP_NAME"],
					"EXCLUDE_USERS" => array($USER->GetID())
				));
			}
		}

		return $bSuccess;
	}

	public static function RejectRequestToBeMember($userID, $groupID, $arRelationID)
	{
		global $APPLICATION, $DB, $USER;

		$userID = intval($userID);
		if ($userID <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UR_EMPTY_USERID"), "ERROR_USERID");
			return false;
		}

		$groupID = intval($groupID);
		if ($groupID <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UR_EMPTY_GROUPID"), "ERROR_GROUPID");
			return false;
		}

		if (!is_array($arRelationID))
			return true;

		$arGroup = CSocNetGroup::GetByID($groupID);
		if (!$arGroup || !is_array($arGroup))
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UG_ERROR_NO_GROUP_ID"), "ERROR_NO_GROUP");
			return false;
		}

		$arGroupSites = array();
		$rsGroupSite = CSocNetGroup::GetSite($groupID);
		while ($arGroupSite = $rsGroupSite->Fetch())
			$arGroupSites[] = $arGroupSite["LID"];

		$userRole = CSocNetUserToGroup::GetUserRole($userID, $groupID);
		$bUserIsMember = ($userRole && in_array($userRole, array(SONET_ROLES_OWNER, SONET_ROLES_MODERATOR, SONET_ROLES_USER)));
		$bCanInitiate = ($USER->IsAdmin() || CSocNetUser::IsCurrentUserModuleAdmin($arGroupSites) || ($userRole
			&& (($arGroup["INITIATE_PERMS"] == SONET_ROLES_OWNER && $userID == $arGroup["OWNER_ID"])
				|| ($arGroup["INITIATE_PERMS"] == SONET_ROLES_MODERATOR && in_array($userRole, array(SONET_ROLES_OWNER, SONET_ROLES_MODERATOR)))
				|| ($arGroup["INITIATE_PERMS"] == SONET_ROLES_USER && $bUserIsMember))));

		if (!$bCanInitiate)
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UG_ERROR_NO_PERMS"), "ERROR_NO_PERMS");
			return false;
		}

		$bSuccess = true;
		$tmp_count = count($arRelationID);
		for ($i = 0; $i < $tmp_count; $i++)
		{
			$arRelationID[$i] = intval($arRelationID[$i]);
			if ($arRelationID[$i] <= 0)
				continue;

			$arRelation = CSocNetUserToGroup::GetByID($arRelationID[$i]);
			if (!$arRelation)
				continue;

			if (
				$arRelation["GROUP_ID"] != $groupID
				|| $arRelation["INITIATED_BY_TYPE"] != SONET_INITIATED_BY_USER
				|| $arRelation["ROLE"] != SONET_ROLES_REQUEST
			)
			{
				continue;
			}

			if (CSocNetUserToGroup::Delete($arRelation["ID"]))
			{
				$arMessageFields = array(
					"FROM_USER_ID" => $userID,
					"TO_USER_ID" => $arRelation["USER_ID"],
					"MESSAGE" => str_replace("#NAME#", $arGroup["NAME"], GetMessage("SONET_UG_REJECT_MEMBER_MESSAGE_G")),
					"=DATE_CREATE" => $DB->CurrentTimeFunction(),
					"MESSAGE_TYPE" => SONET_MESSAGE_SYSTEM
				);
				CSocNetMessages::Add($arMessageFields);
			}
			else
			{
				$errorMessage = "";
				if ($e = $APPLICATION->GetException())
					$errorMessage = $e->GetString();
				if ($errorMessage == '')
					$errorMessage = GetMessage("SONET_UR_ERROR_CREATE_USER2GROUP");

				$APPLICATION->ThrowException($errorMessage, "ERROR_CONFIRM_MEMBER");
				$bSuccess = false;
			}
		}

		return $bSuccess;
	}

	public static function UserConfirmRequestToBeMember($targetUserID, $relationID, $bAutoSubscribe = true) // request from group confirmed by a user
	{
		global $APPLICATION, $DB;

		$targetUserID = intval($targetUserID);
		if ($targetUserID <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UR_EMPTY_USERID"), "ERROR_SENDER_USER_ID");
			return false;
		}

		$relationID = intval($relationID);
		if ($relationID <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UR_EMPTY_RELATIONID"), "ERROR_RELATION_ID");
			return false;
		}

		$dbResult = CSocNetUserToGroup::GetList(
			array(),
			array(
				"ID" => $relationID,
				"USER_ID" => $targetUserID,
				"ROLE" => SONET_ROLES_REQUEST,
				"INITIATED_BY_TYPE" => SONET_INITIATED_BY_GROUP
			),
			false,
			false,
			array("ID", "USER_ID", "INITIATED_BY_USER_ID", "GROUP_ID", "GROUP_VISIBLE", "GROUP_SITE_ID", "GROUP_NAME")
		);

		if ($arResult = $dbResult->Fetch())
		{
			$arFields = array(
				"ROLE" => UserToGroupTable::ROLE_USER,
				"=DATE_UPDATE" => $DB->CurrentTimeFunction(),
			);
			if (CSocNetUserToGroup::Update($arResult["ID"], $arFields))
			{
				$events = GetModuleEvents("socialnetwork", "OnSocNetUserConfirmRequestToBeMember");
				while ($arEvent = $events->Fetch())
				{
					ExecuteModuleEventEx($arEvent, array($arResult["ID"], $arResult));
				}

				if ($bAutoSubscribe)
				{
					CSocNetLogEvents::AutoSubscribe($targetUserID, SONET_ENTITY_GROUP, $arResult["GROUP_ID"]);
				}

				if (CModule::IncludeModule("im"))
				{
					$groupSiteId = CSocNetGroup::GetDefaultSiteId($arResult["GROUP_ID"], $arResult["GROUP_SITE_ID"]);

					CIMNotify::DeleteByTag("SOCNET|INVITE_GROUP|".intval($targetUserID)."|".intval($relationID));

					$workgroupsPage = COption::GetOptionString("socialnetwork", "workgroups_page", "/workgroups/", $groupSiteId);
					$groupUrlTemplate = COption::GetOptionString("socialnetwork", "group_path_template", "/workgroups/group/#group_id#/", $groupSiteId);
					$groupUrlTemplate = "#GROUPS_PATH#".mb_substr($groupUrlTemplate, mb_strlen($workgroupsPage), mb_strlen($groupUrlTemplate) - mb_strlen($workgroupsPage));
					$groupUrl = str_replace(array("#group_id#", "#GROUP_ID#"), $arResult["GROUP_ID"], $groupUrlTemplate);
					
					$arTmp = CSocNetLogTools::ProcessPath(
						array(
							"GROUP_URL" => $groupUrl
						), 
						$arResult["INITIATED_BY_USER_ID"], 
						$groupSiteId
					);
					$url = $arTmp["URLS"]["GROUP_URL"];
					$serverName = (
					mb_strpos($url, "http://") === 0
						|| mb_strpos($url, "https://") === 0
							? ""
							: $arTmp["SERVER_NAME"]
					);
					$domainName = (
					mb_strpos($url, "http://") === 0
						|| mb_strpos($url, "https://") === 0
							? ""
							: (
								isset($arTmp["DOMAIN"]) 
								&& !empty($arTmp["DOMAIN"]) 
									? "//".$arTmp["DOMAIN"]
									: ""
							)
					);

					$arMessageFields = array(
						"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
						"TO_USER_ID" => $arResult['USER_ID'],
						"NOTIFY_TYPE" => IM_NOTIFY_SYSTEM,
						"NOTIFY_MODULE" => "socialnetwork",
						"NOTIFY_EVENT" => "invite_group",
						"NOTIFY_TAG" => "SOCNET|INVITE_GROUP|".intval($arResult['USER_ID'])."|".$relationID,
						"NOTIFY_MESSAGE" => str_replace(
							"#NAME#",
							"<a href=\"".$domainName.$url."\" class=\"bx-notifier-item-action\">".$arResult["GROUP_NAME"]."</a>",
							GetMessage("SONET_UG_CONFIRM_MEMBER_MESSAGE_G")
						),
						"NOTIFY_MESSAGE_OUT" => str_replace(
							"#NAME#",
							$arResult["GROUP_NAME"],
							GetMessage("SONET_UG_CONFIRM_MEMBER_MESSAGE_G")." (".$serverName.$url.")"
						)
					);
					CIMNotify::Add($arMessageFields);

					$chatNotificationResult = UserToGroup::addInfoToChat(array(
						'group_id' => $arResult["GROUP_ID"],
						'user_id' => $arResult["USER_ID"],
						'action' => UserToGroup::CHAT_ACTION_IN,
						'role' => $arFields['ROLE']
					));

					if (!$chatNotificationResult)
					{
						$arMessageFields = array(
							"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
							"TO_USER_ID" => $arResult["INITIATED_BY_USER_ID"],
							"FROM_USER_ID" => $arResult['USER_ID'],
							"NOTIFY_TYPE" => IM_NOTIFY_FROM,
							"NOTIFY_MODULE" => "socialnetwork",
							"NOTIFY_EVENT" => "invite_group",
							"NOTIFY_TAG" => "SOCNET|INVITE_GROUP_SUCCESS|".intval($arResult["GROUP_ID"]),
							"NOTIFY_MESSAGE" => str_replace(
								"#NAME#",
								"<a href=\"".$domainName.$url."\" class=\"bx-notifier-item-action\">".$arResult["GROUP_NAME"]."</a>",
								GetMessage("SONET_UG_CONFIRM_MEMBER_MESSAGE")
							),
							"NOTIFY_MESSAGE_OUT" => str_replace("#NAME#", $arResult["GROUP_NAME"], GetMessage("SONET_UG_CONFIRM_MEMBER_MESSAGE")." (".$serverName.$url.")"),
						);
						CIMNotify::Add($arMessageFields);

						CSocNetUserToGroup::NotifyImToModerators(array(
							"TYPE" => "join",
							"RELATION_ID" => $arResult["ID"],
							"USER_ID" => $arResult["USER_ID"],
							"GROUP_ID" => $arResult["GROUP_ID"],
							"GROUP_NAME" => htmlspecialcharsbx($arResult["GROUP_NAME"]),
							"EXCLUDE_USERS" => array($arResult["INITIATED_BY_USER_ID"])
						));
					}
				}

			}
			else
			{
				$errorMessage = "";
				if ($e = $APPLICATION->GetException())
					$errorMessage = $e->GetString();
				if ($errorMessage == '')
					$errorMessage = GetMessage("SONET_UR_ERROR_CREATE_USER2GROUP");

				$APPLICATION->ThrowException($errorMessage, "ERROR_CREATE_RELATION");
				return false;
			}
		}
		else
		{
			$APPLICATION->ThrowException(GetMessage("SONET_NO_USER2GROUP"), "ERROR_NO_GROUP_REQUEST");
			return false;
		}

		CSocNetUserToGroup::__SpeedFileCheckMessages($targetUserID);

		return true;
	}

	public static function UserRejectRequestToBeMember($targetUserID, $relationID)
	{
		global $APPLICATION;

		$targetUserID = intval($targetUserID);
		if ($targetUserID <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UR_EMPTY_USERID"), "ERROR_SENDER_USER_ID");
			return false;
		}

		$relationID = intval($relationID);
		if ($relationID <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UR_EMPTY_RELATIONID"), "ERROR_RELATION_ID");
			return false;
		}

		$dbResult = CSocNetUserToGroup::GetList(
			array(),
			array(
				"ID" => $relationID,
				"USER_ID" => $targetUserID,
				"ROLE" => SONET_ROLES_REQUEST,
				"INITIATED_BY_TYPE" => SONET_INITIATED_BY_GROUP
			),
			false,
			false,
			array("ID", "USER_ID", "GROUP_ID", "GROUP_SITE_ID", "INITIATED_BY_USER_ID", "GROUP_NAME")
		);

		if ($arResult = $dbResult->Fetch())
		{
			if (CSocNetUserToGroup::Delete($arResult["ID"]))
			{
				$events = GetModuleEvents("socialnetwork", "OnSocNetUserRejectRequestToBeMember");
				while ($arEvent = $events->Fetch())
				{
					ExecuteModuleEventEx($arEvent, array($arResult["ID"], $arResult));
				}

				if (CModule::IncludeModule("im"))
				{
					$groupSiteId = CSocNetGroup::GetDefaultSiteId($arResult["GROUP_ID"], $arResult["GROUP_SITE_ID"]);
					$groupUrl = str_replace(array("#group_id#", "#GROUP_ID#"), $arResult["GROUP_ID"], COption::GetOptionString("socialnetwork", "group_path_template", "/workgroups/group/#group_id#/", $groupSiteId));
					$arTmp = CSocNetLogTools::ProcessPath(
						array(
							"GROUP_URL" => $groupUrl
						), 
						$arResult["INITIATED_BY_USER_ID"],
						$groupSiteId
					);
					$url = $arTmp["URLS"]["GROUP_URL"];
					$serverName = (
					mb_strpos($url, "http://") === 0
						|| mb_strpos($url, "https://") === 0
							? ""
							: $arTmp["SERVER_NAME"]
					);
					$domainName = (
					mb_strpos($url, "http://") === 0
						|| mb_strpos($url, "https://") === 0
							? ""
							: (
								isset($arTmp["DOMAIN"]) 
								&& !empty($arTmp["DOMAIN"]) 
									? "//".$arTmp["DOMAIN"]
									: ""
							)
					);

					$arMessageFields = array(
						"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
						"TO_USER_ID" => $arResult["INITIATED_BY_USER_ID"],
						"FROM_USER_ID" => $arResult['USER_ID'],
						"NOTIFY_TYPE" => IM_NOTIFY_FROM,
						"NOTIFY_MODULE" => "socialnetwork",
						"NOTIFY_EVENT" => "invite_group",
						"NOTIFY_TAG" => "SOCNET|INVITE_GROUP_REJECT|".intval($arResult["GROUP_ID"]),
						"NOTIFY_MESSAGE" => str_replace(
							"#NAME#", 
							"<a href=\"".$domainName.$url."\" class=\"bx-notifier-item-action\">".$arResult["GROUP_NAME"]."</a>", 
							GetMessage("SONET_UG_REJECT_MEMBER_MESSAGE")
						),
						"NOTIFY_MESSAGE_OUT" => str_replace(
							"#NAME#", 
							$arResult["GROUP_NAME"], 
							GetMessage("SONET_UG_REJECT_MEMBER_MESSAGE")." (".$serverName.$url.")"
						)
					);
					CIMNotify::Add($arMessageFields);
				}
			}
			else
			{
				$errorMessage = "";
				if ($e = $APPLICATION->GetException())
					$errorMessage = $e->GetString();
				if ($errorMessage == '')
					$errorMessage = GetMessage("SONET_UR_ERROR_CREATE_USER2GROUP");

				$APPLICATION->ThrowException($errorMessage, "ERROR_DELETE_RELATION");
				return false;
			}
		}
		else
		{
			$APPLICATION->ThrowException(GetMessage("SONET_NO_USER2GROUP"), "ERROR_NO_MEMBER_REQUEST");
			return false;
		}

		CSocNetUserToGroup::__SpeedFileCheckMessages($targetUserID);

		return true;
	}

	public static function TransferModerator2Member($userID, $groupID, $arRelationID, $currentUserIsAdmin)
	{
		global $APPLICATION, $DB, $USER;

		$userID = intval($userID);
		if ($userID <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UR_EMPTY_USERID"), "ERROR_USERID");
			return false;
		}

		$groupID = intval($groupID);
		if ($groupID <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UR_EMPTY_GROUPID"), "ERROR_GROUPID");
			return false;
		}

		if (!is_array($arRelationID))
			return true;

		$arGroup = CSocNetGroup::GetByID($groupID);
		if (!$arGroup || !is_array($arGroup))
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UG_ERROR_NO_GROUP_ID"), "ERROR_NO_GROUP");
			return false;
		}

		$arUserPerms = CSocNetUserToGroup::InitUserPerms($userID, $arGroup, $currentUserIsAdmin);

		if (!$arUserPerms["UserCanModifyGroup"])
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UG_ERROR_NO_PERMS"), "ERROR_NO_PERMS");
			return false;
		}

		$bSuccess = true;
		$arSuccessRelations = array();
		$bIMIncluded = false;
		$groupSiteId = SITE_ID;

		if (CModule::IncludeModule("im"))
		{
			$bIMIncluded = true;
			$groupSiteId = CSocNetGroup::GetDefaultSiteId($groupID, $arGroup["SITE_ID"]);
		}

		$workgroupsPage = COption::GetOptionString("socialnetwork", "workgroups_page", "/workgroups/", $groupSiteId);
		$groupUrlTemplate = COption::GetOptionString("socialnetwork", "group_path_template", "/workgroups/group/#group_id#/", $groupSiteId);
		$groupUrlTemplate = "#GROUPS_PATH#".mb_substr($groupUrlTemplate, mb_strlen($workgroupsPage), mb_strlen($groupUrlTemplate) - mb_strlen($workgroupsPage));
		$groupUrl = str_replace(array("#group_id#", "#GROUP_ID#"), $groupID, $groupUrlTemplate);
		$relationsToUpdateCount = 0;

		foreach($arRelationID as $key => $relationId)
		{
			$relationId = intval($relationId);
			if ($relationId <= 0)
			{
				continue;
			}

			$arRelation = CSocNetUserToGroup::GetByID($relationId);
			if (
				!$arRelation
				|| $arRelation["GROUP_ID"] != $groupID 
				|| $arRelation["ROLE"] != SONET_ROLES_MODERATOR
			)
			{
				continue;
			}

			$relationsToUpdateCount++;

			$arFields = array(
				"ROLE" => SONET_ROLES_USER,
				"=DATE_UPDATE" => $DB->CurrentTimeFunction(),
			);
			if (CSocNetUserToGroup::Update($arRelation["ID"], $arFields))
			{
				$arSuccessRelations[] = $arRelation;

				if ($bIMIncluded)
				{
					$arTmp = CSocNetLogTools::ProcessPath(
						array(
							"GROUP_URL" => $groupUrl
						),
						$arRelation["USER_ID"],
						$groupSiteId
					);
					$groupUrl = $arTmp["URLS"]["GROUP_URL"];
					$serverName = (
					mb_strpos($groupUrl, "http://") === 0
						|| mb_strpos($groupUrl, "https://") === 0
							? ""
							: $arTmp["SERVER_NAME"]
					);
					$domainName = (
					mb_strpos($groupUrl, "http://") === 0
						|| mb_strpos($groupUrl, "https://") === 0
							? ""
							: (
								isset($arTmp["DOMAIN"]) 
								&& !empty($arTmp["DOMAIN"]) 
									? "//".$arTmp["DOMAIN"]
									: ""
							)
					);

					$arMessageFields = array(
						"TO_USER_ID" => $arRelation["USER_ID"],
						"FROM_USER_ID" => $userID,
						"NOTIFY_TYPE" => IM_NOTIFY_FROM,
						"NOTIFY_MODULE" => "socialnetwork",
						"NOTIFY_EVENT" => "moderators_group",
						"NOTIFY_TAG" => "SOCNET|MOD_GROUP|".intval($userID)."|".$groupID."|".$arRelation["ID"]."|".$arRelation["USER_ID"],
						"NOTIFY_MESSAGE" => str_replace(
							array("#NAME#"), 
							array("<a href=\"".$domainName.$groupUrl."\" class=\"bx-notifier-item-action\">".$arGroup["NAME"]."</a>"), 
							GetMessage("SONET_UG_MOD2MEMBER_MESSAGE")
						),
						"NOTIFY_MESSAGE_OUT" => str_replace(
							array("#NAME#"), 
							array($arGroup["NAME"]), 
							GetMessage("SONET_UG_MOD2MEMBER_MESSAGE")
						)." (".$serverName.$groupUrl.")"
					);

					CIMNotify::Add($arMessageFields);
				}
			}
			else
			{
				$errorMessage = "";
				if ($e = $APPLICATION->GetException())
					$errorMessage = $e->GetString();
				if ($errorMessage == '')
					$errorMessage = GetMessage("SONET_UR_ERROR_CREATE_USER2GROUP");

				$APPLICATION->ThrowException($errorMessage, "ERROR_MOD2MEMBER");
				$bSuccess = false;
			}
		}

		if ($relationsToUpdateCount <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UR_ERROR_MEM2MOD_EMPTY_CORRECT_LIST"), "MOD2MEM_EMPTY_CORRECT_LIST");
			return false;
		}

		$successfulUserIdList = array();
		foreach($arSuccessRelations as $arRel)
		{
			$arNotifyParams = array(
				"TYPE" => "unmoderate",
				"RELATION_ID" => $arRel["ID"],
				"USER_ID" => $arRel["USER_ID"],
				"GROUP_ID" => $arRel["GROUP_ID"],
				"GROUP_NAME" => $arRel["GROUP_NAME"],
				"EXCLUDE_USERS" => array($USER->GetID())
			);
			CSocNetUserToGroup::NotifyImToModerators($arNotifyParams);

			$successfulUserIdList[] = $arRel["USER_ID"];
		}

		$successfulUserIdList = array_unique($successfulUserIdList);

		if (!empty($successfulUserIdList))
		{
			Integration\Im\Chat\Workgroup::setChatManagers(array(
				'group_id' => $groupID,
				'user_id' => $successfulUserIdList,
				'set' => false
			));
		}

		if (
			$bSuccess
			&& count($arSuccessRelations) <= 0
		)
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UR_ERROR_MOD2MEM_INCORRECT_PARAMS"), "MOD2MEM_INCORRECT_PARAMS");
			$bSuccess = false;
		}

		return $bSuccess;
	}

	public static function TransferMember2Moderator($userID, $groupID, $arRelationID, $currentUserIsAdmin)
	{
		global $APPLICATION, $DB, $USER;

		$userID = intval($userID);
		if ($userID <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UR_EMPTY_USERID"), "ERROR_USERID");
			return false;
		}

		$groupID = intval($groupID);
		if ($groupID <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UR_EMPTY_GROUPID"), "ERROR_GROUPID");
			return false;
		}

		if (!is_array($arRelationID))
			return true;

		$arGroup = CSocNetGroup::GetByID($groupID);
		if (!$arGroup || !is_array($arGroup))
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UG_ERROR_NO_GROUP_ID"), "ERROR_NO_GROUP");
			return false;
		}

		$arUserPerms = CSocNetUserToGroup::InitUserPerms($userID, $arGroup, $currentUserIsAdmin);

		if (!$arUserPerms["UserCanModifyGroup"])
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UG_ERROR_NO_PERMS"), "ERROR_NO_PERMS");
			return false;
		}

		$bSuccess = true;
		$arSuccessRelations = array();

		$relationsToUpdateCount = 0;

		foreach($arRelationID as $key => $relationId)
		{
			$relationId = intval($relationId);
			if ($relationId <= 0)
			{
				continue;
			}

			$arRelation = CSocNetUserToGroup::GetByID($relationId);
			if (
				!$arRelation
				|| $arRelation["GROUP_ID"] != $groupID 
				|| $arRelation["ROLE"] != SONET_ROLES_USER
			)
			{
				continue;
			}

			$relationsToUpdateCount++;

			$arFields = array(
				"ROLE" => SONET_ROLES_MODERATOR,
				"=DATE_UPDATE" => $DB->CurrentTimeFunction(),
			);
			if (CSocNetUserToGroup::update($arRelation["ID"], $arFields))
			{
				$arSuccessRelations[] = $arRelation;
				self::notifyModeratorAdded(array(
					'userId' => $userID,
					'groupId' => $groupID,
					'relationFields' => $arRelation,
					'groupFields' => $arGroup
				));
			}
			else
			{
				$errorMessage = "";
				if ($e = $APPLICATION->GetException())
					$errorMessage = $e->GetString();
				if ($errorMessage == '')
					$errorMessage = GetMessage("SONET_UR_ERROR_CREATE_USER2GROUP");

				$APPLICATION->ThrowException($errorMessage, "ERROR_MEMBER2MOD");
				$bSuccess = false;
			}
		}

		if ($relationsToUpdateCount <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UR_ERROR_MEM2MOD_EMPTY_CORRECT_LIST"), "MOD2MEM_EMPTY_CORRECT_LIST");
			return false;
		}

		$successfulUserIdList = array();
		foreach($arSuccessRelations as $arRel)
		{
			$arNotifyParams = array(
				"TYPE" => "moderate",
				"RELATION_ID" => $arRel["ID"],
				"USER_ID" => $arRel["USER_ID"],
				"GROUP_ID" => $arRel["GROUP_ID"],
				"GROUP_NAME" => $arRel["GROUP_NAME"],
				"EXCLUDE_USERS" => array($arRel["USER_ID"], $USER->GetID())
			);
			CSocNetUserToGroup::NotifyImToModerators($arNotifyParams);
			CSocNetSubscription::Set($arRel["USER_ID"], "SG".$arRel["GROUP_ID"], "Y");

			$successfulUserIdList[] = $arRel["USER_ID"];
		}

		$successfulUserIdList = array_unique($successfulUserIdList);

		if (!empty($successfulUserIdList))
		{
			Integration\Im\Chat\Workgroup::setChatManagers(array(
				'group_id' => $groupID,
				'user_id' => $successfulUserIdList,
				'set' => true
			));
		}

		if (
			$bSuccess
			&& count($arSuccessRelations) <= 0
		)
		{
			$errorMessage = "";
			if ($e = $APPLICATION->GetException())
				$errorMessage = $e->GetString();
			if ($errorMessage == '')
				$errorMessage = GetMessage("SONET_UR_ERROR_MEM2MOD_INCORRECT_PARAMS");

			$APPLICATION->ThrowException($errorMessage, "MEM2MOD_INCORRECT_PARAMS");
			$bSuccess = false;
		}

		return $bSuccess;
	}

	public static function BanMember($userID, $groupID, $arRelationID, $currentUserIsAdmin)
	{
		global $APPLICATION, $DB;

		$userID = intval($userID);
		if ($userID <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UR_EMPTY_USERID"), "ERROR_USERID");
			return false;
		}

		$groupID = intval($groupID);
		if ($groupID <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UR_EMPTY_GROUPID"), "ERROR_GROUPID");
			return false;
		}

		if (!is_array($arRelationID))
			return true;

		$arGroup = CSocNetGroup::GetByID($groupID);
		if (!$arGroup || !is_array($arGroup))
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UG_ERROR_NO_GROUP_ID"), "ERROR_NO_GROUP");
			return false;
		}

		$arUserPerms = CSocNetUserToGroup::InitUserPerms($userID, $arGroup, $currentUserIsAdmin);

		if (!$arUserPerms["UserCanModifyGroup"] && !$arUserPerms["UserCanModerateGroup"])
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UG_ERROR_NO_PERMS"), "ERROR_NO_PERMS");
			return false;
		}

		$bSuccess = true;
		$tmp_count = count($arRelationID);
		for ($i = 0; $i < $tmp_count; $i++)
		{
			$arRelationID[$i] = intval($arRelationID[$i]);
			if ($arRelationID[$i] <= 0)
				continue;

			$arRelation = CSocNetUserToGroup::GetByID($arRelationID[$i]);
			if (!$arRelation)
				continue;

			if ($arRelation["GROUP_ID"] != $groupID || $arRelation["ROLE"] != SONET_ROLES_USER)
				continue;

			$arFields = array(
				"ROLE" => SONET_ROLES_BAN,
				"=DATE_UPDATE" => $DB->CurrentTimeFunction(),
			);
			if (CSocNetUserToGroup::Update($arRelation["ID"], $arFields))
			{
				$arMessageFields = array(
					"FROM_USER_ID" => $userID,
					"TO_USER_ID" => $arRelation["USER_ID"],
					"MESSAGE" => str_replace("#NAME#", $arGroup["NAME"], GetMessage("SONET_UG_BANMEMBER_MESSAGE")),
					"=DATE_CREATE" => $DB->CurrentTimeFunction(),
					"MESSAGE_TYPE" => SONET_MESSAGE_SYSTEM
				);
				CSocNetMessages::Add($arMessageFields);
				CSocNetSubscription::DeleteEx($arRelation["USER_ID"], "SG".$arRelation["GROUP_ID"]);

				UserToGroup::addInfoToChat(array(
					'group_id' => $groupID,
					'user_id' => $arRelation["USER_ID"],
					'action' => UserToGroup::CHAT_ACTION_OUT
				));
			}
			else
			{
				$errorMessage = "";
				if ($e = $APPLICATION->GetException())
					$errorMessage = $e->GetString();
				if ($errorMessage == '')
					$errorMessage = GetMessage("SONET_UR_ERROR_CREATE_USER2GROUP");

				$APPLICATION->ThrowException($errorMessage, "ERROR_BANMEMBER");
				$bSuccess = false;
			}
		}

		return $bSuccess;
	}

	public static function UnBanMember($userID, $groupID, $arRelationID, $currentUserIsAdmin)
	{
		global $APPLICATION, $DB;

		$userID = intval($userID);
		if ($userID <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UR_EMPTY_USERID"), "ERROR_USERID");
			return false;
		}

		$groupID = intval($groupID);
		if ($groupID <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UR_EMPTY_GROUPID"), "ERROR_GROUPID");
			return false;
		}

		if (!is_array($arRelationID))
			return true;

		$arGroup = CSocNetGroup::GetByID($groupID);
		if (!$arGroup || !is_array($arGroup))
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UG_ERROR_NO_GROUP_ID"), "ERROR_NO_GROUP");
			return false;
		}

		$arUserPerms = CSocNetUserToGroup::InitUserPerms($userID, $arGroup, $currentUserIsAdmin);

		if (!$arUserPerms["UserCanModifyGroup"] && !$arUserPerms["UserCanModerateGroup"])
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UG_ERROR_NO_PERMS"), "ERROR_NO_PERMS");
			return false;
		}

		$bSuccess = true;
		$tmp_count = count($arRelationID);
		for ($i = 0; $i < $tmp_count; $i++)
		{
			$arRelationID[$i] = intval($arRelationID[$i]);
			if ($arRelationID[$i] <= 0)
				continue;

			$arRelation = CSocNetUserToGroup::GetByID($arRelationID[$i]);
			if (!$arRelation)
				continue;

			if ($arRelation["GROUP_ID"] != $groupID || $arRelation["ROLE"] != SONET_ROLES_BAN)
				continue;

			$arFields = array(
				"ROLE" => UserToGroupTable::ROLE_USER,
				"=DATE_UPDATE" => $DB->CurrentTimeFunction(),
			);
			if (CSocNetUserToGroup::Update($arRelation["ID"], $arFields))
			{
				CSocNetMessages::Add(array(
					"FROM_USER_ID" => $userID,
					"TO_USER_ID" => $arRelation["USER_ID"],
					"MESSAGE" => str_replace("#NAME#", $arGroup["NAME"], GetMessage("SONET_UG_UNBANMEMBER_MESSAGE")),
					"=DATE_CREATE" => $DB->CurrentTimeFunction(),
					"MESSAGE_TYPE" => SONET_MESSAGE_SYSTEM
				));

				UserToGroup::addInfoToChat(array(
					'group_id' => $groupID,
					'user_id' => $userID,
					'action' => UserToGroup::CHAT_ACTION_IN,
					'role' => $arFields['ROLE']
				));
			}
			else
			{
				$errorMessage = "";
				if ($e = $APPLICATION->GetException())
					$errorMessage = $e->GetString();
				if ($errorMessage == '')
					$errorMessage = GetMessage("SONET_UR_ERROR_CREATE_USER2GROUP");

				$APPLICATION->ThrowException($errorMessage, "ERROR_UNBANMEMBER");
				$bSuccess = false;
			}
		}

		return $bSuccess;
	}

	public static function SetOwner($userID, $groupID, $arGroup = false)
	{
		global $DB, $APPLICATION, $USER;

		if (!$arGroup)
		{
			$arGroup = CSocNetGroup::GetByID($groupID);
		}

		if (!$arGroup)
		{
			return false;
		}

		$errorMessage = "";
		$DB->StartTransaction();

		// setting relations for the old owner
		$dbRelation = CSocNetUserToGroup::GetList(
			array(), 
			array(
				"USER_ID" => $arGroup["OWNER_ID"], 
				"GROUP_ID" => $groupID
			), 
			false, 
			false, 
			array("ID")
		);
		if ($arRelation = $dbRelation->Fetch())
		{
			$arFields = array(
				"ROLE" => SONET_ROLES_USER,
				"=DATE_UPDATE" => $DB->CurrentTimeFunction(),
				"INITIATED_BY_TYPE" => SONET_INITIATED_BY_USER,
				"INITIATED_BY_USER_ID" => $USER->GetID(),
			);

			if (!CSocNetUserToGroup::Update($arRelation["ID"], $arFields))
			{
				if ($e = $APPLICATION->GetException())
				{
					$errorMessage = $e->GetString();
				}
				if ($errorMessage == '')
				{
					$errorMessage = GetMessage("SONET_UG_ERROR_CANNOT_UPDATE_CURRENT_OWNER");
				}

				$APPLICATION->ThrowException($errorMessage, "ERROR_UPDATE_USER2GROUP");
				$DB->Rollback();
				return false;
			}
		}
		else
		{
			if ($e = $APPLICATION->GetException())
			{
				$errorMessage = $e->GetString();
			}
			if ($errorMessage == '')
			{
				$errorMessage = GetMessage("SONET_UG_ERROR_CANNOT_GET_CURRENT_OWNER_RELATION");
			}

			$APPLICATION->ThrowException($errorMessage, "ERROR_GET_USER2GROUP");
			$DB->Rollback();
			return false;
		}

		// delete requests to the old owner
		if ($errorMessage == '')
			CSocNetUserToGroup::__SpeedFileDelete($arGroup["OWNER_ID"]);

		if ($errorMessage == '')
		{
			// setting relations for the new owner
			$dbRelation = CSocNetUserToGroup::GetList(array(), array("USER_ID" => $userID, "GROUP_ID" => $groupID), false, false, array("ID"));
			if ($arRelation = $dbRelation->Fetch())
			{
				$arFields = array(
					"ROLE" => SONET_ROLES_OWNER,
					"=DATE_UPDATE" => $DB->CurrentTimeFunction(),
					"INITIATED_BY_TYPE" => SONET_INITIATED_BY_USER,
					"INITIATED_BY_USER_ID" => $USER->GetID(),
					"AUTO_MEMBER" => "N"
				);

				if (!CSocNetUserToGroup::Update($arRelation["ID"], $arFields))
				{
					if ($e = $APPLICATION->GetException())
						$errorMessage = $e->GetString();
					if ($errorMessage == '')
						$errorMessage = GetMessage("SONET_UG_ERROR_CANNOT_UPDATE_NEW_OWNER_RELATION");

					$APPLICATION->ThrowException($errorMessage, "ERROR_UPDATE_USER2GROUP");
					$DB->Rollback();
					return false;
				}
			}
			else
			{
				$arFields = array(
					"USER_ID" => $userID,
					"GROUP_ID" => $groupID,
					"ROLE" => SONET_ROLES_OWNER,
					"=DATE_CREATE" => $DB->CurrentTimeFunction(),
					"=DATE_UPDATE" => $DB->CurrentTimeFunction(),
					"INITIATED_BY_TYPE" => SONET_INITIATED_BY_USER,
					"INITIATED_BY_USER_ID" => $USER->GetID(),
					"MESSAGE" => false,
				);

				if (!CSocNetUserToGroup::Add($arFields))
				{
					if ($e = $APPLICATION->GetException())
						$errorMessage = $e->GetString();
					if ($errorMessage == '')
						$errorMessage = GetMessage("SONET_UG_ERROR_CANNOT_ADD_NEW_OWNER_RELATION");

					$APPLICATION->ThrowException($errorMessage, "ERROR_ADD_USER2GROUP");
					$DB->Rollback();
					return false;
				}
				else
				{
					UserToGroup::addInfoToChat(array(
						'group_id' => $groupID,
						'user_id' => $userID,
						'action' => UserToGroup::CHAT_ACTION_IN,
						'role' => $arFields['ROLE']
					));
				}
			}
		}

		if ($errorMessage == '')
		{
			$GROUP_ID = CSocNetGroup::Update($groupID, array("OWNER_ID" => $userID));
			if (!$GROUP_ID || intval($GROUP_ID) <= 0)
			{
				if ($e = $APPLICATION->GetException())
					$errorMessage = $e->GetString();
				if ($errorMessage == '')
					$errorMessage = GetMessage("SONET_UG_ERROR_CANNOT_UPDATE_GROUP");

				$APPLICATION->ThrowException($errorMessage, "ERROR_UPDATE_GROUP");
				$DB->Rollback();
				return false;
			}
		}

		$bIMIncluded = false;
		$groupUrl = "";
		$groupSiteId = SITE_ID;

		if (CModule::IncludeModule("im"))
		{
			$bIMIncluded = true;
			$groupSiteId = CSocNetGroup::GetDefaultSiteId($groupID, $arGroup["SITE_ID"]);
			$workgroupsPage = COption::GetOptionString("socialnetwork", "workgroups_page", "/workgroups/", $groupSiteId);
			$groupUrlTemplate = COption::GetOptionString("socialnetwork", "group_path_template", "/workgroups/group/#group_id#/", $groupSiteId);
			$groupUrlTemplate = "#GROUPS_PATH#".mb_substr($groupUrlTemplate, mb_strlen($workgroupsPage), mb_strlen($groupUrlTemplate) - mb_strlen($workgroupsPage));
			$groupUrl = str_replace(array("#group_id#", "#GROUP_ID#"), $groupID, $groupUrlTemplate);
		}

		// send message to the old owner
		if ($bIMIncluded)
		{
			$arTmp = CSocNetLogTools::ProcessPath(
				array(
					"GROUP_URL" => $groupUrl
				), 
				$arGroup["OWNER_ID"],
				$groupSiteId
			);
			$groupUrl = $arTmp["URLS"]["GROUP_URL"];
			$serverName = (
			mb_strpos($groupUrl, "http://") === 0
				|| mb_strpos($groupUrl, "https://") === 0
					? ""
					: $arTmp["SERVER_NAME"]
			);

			$arMessageFields = array(
				"TO_USER_ID" => $arGroup["OWNER_ID"],
				"FROM_USER_ID" => $USER->GetID(),
				"NOTIFY_TYPE" => IM_NOTIFY_FROM,
				"NOTIFY_MODULE" => "socialnetwork",
				"NOTIFY_EVENT" => "owner_group",
				"NOTIFY_TAG" => "SOCNET|OWNER_GROUP|".$groupID,
				"NOTIFY_MESSAGE" => str_replace(
					"#NAME#",
					"<a href=\"".$groupUrl."\" class=\"bx-notifier-item-action\">".$arGroup["NAME"]."</a>",
					GetMessage("SONET_UG_OWNER2MEMBER_MESSAGE")
				),
				"NOTIFY_MESSAGE_OUT" => str_replace(
					"#NAME#",
					$arGroup["NAME"],
					GetMessage("SONET_UG_OWNER2MEMBER_MESSAGE")." (".$serverName.$groupUrl.")"
				)
			);

			CIMNotify::Add($arMessageFields);
		}

		// send message to the new owner
		if ($bIMIncluded)
		{
			$arTmp = CSocNetLogTools::ProcessPath(
				array(
					"GROUP_URL" => $groupUrl
				), 
				$userID,
				$groupSiteId
			);
			$groupUrl = $arTmp["URLS"]["GROUP_URL"];

			if (
				mb_strpos($groupUrl, "http://") === 0
				|| mb_strpos($groupUrl, "https://") === 0
			)
				$serverName = "";
			else
				$serverName = $arTmp["SERVER_NAME"];

			$arMessageFields = array(
				"TO_USER_ID" => $userID,
				"FROM_USER_ID" => $USER->GetID(),
				"NOTIFY_TYPE" => IM_NOTIFY_FROM,
				"NOTIFY_MODULE" => "socialnetwork",
				"NOTIFY_EVENT" => "owner_group",
				"NOTIFY_TAG" => "SOCNET|OWNER_GROUP|".$groupID,
				"NOTIFY_MESSAGE" => str_replace(
					"#NAME#",
					"<a href=\"".$groupUrl."\" class=\"bx-notifier-item-action\">".$arGroup["NAME"]."</a>",
					GetMessage("SONET_UG_MEMBER2OWNER_MESSAGE")
				),
				"NOTIFY_MESSAGE_OUT" => str_replace(
					"#NAME#",
					$arGroup["NAME"],
					GetMessage("SONET_UG_MEMBER2OWNER_MESSAGE")." (".$serverName.$groupUrl.")"
				)
			);

			CIMNotify::Add($arMessageFields);
		}

		$arNotifyParams = array(
			"TYPE" => "owner",
			"RELATION_ID" => $arRelation["ID"],
			"USER_ID" => $userID,
			"GROUP_ID" => $groupID,
			"GROUP_NAME" => htmlspecialcharsbx($arGroup["NAME"]),
			"EXCLUDE_USERS" => array($userID, $arGroup["OWNER_ID"], $USER->GetID())
		);
		CSocNetUserToGroup::NotifyImToModerators($arNotifyParams);

		CSocNetSubscription::Set($userID, "SG".$groupID, "Y");

		if ($errorMessage == '')
		{
			$DB->Commit();
			return true;
		}
		else
		{
			$DB->Rollback();
			return false;	
		}
	}

	public static function DeleteRelation($userID, $groupID)
	{
		global $APPLICATION;

		$userID = intval($userID);
		if ($userID <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UR_EMPTY_USERID"), "ERROR_USER_ID");
			return false;
		}

		$groupID = intval($groupID);
		if ($groupID <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("SONET_UR_EMPTY_GROUPID"), "ERROR_GROUPID");
			return false;
		}

		$dbResult = CSocNetUserToGroup::GetList(
			array(),
			array(
				"GROUP_ID" => $groupID,
				"USER_ID" => $userID,
			),
			false,
			false,
			array("ID", "ROLE", "GROUP_VISIBLE", "GROUP_NAME")
		);

		if ($arResult = $dbResult->Fetch())
		{
			if (!in_array($arResult["ROLE"], array(UserToGroupTable::ROLE_USER, UserToGroupTable::ROLE_MODERATOR)))
			{
				return false;
			}

			if (CSocNetUserToGroup::Delete($arResult["ID"]))
			{
				CSocNetSubscription::DeleteEx($userID, "SG".$groupID);

				if (IsModuleInstalled("im"))
				{
					$chatNotificationResult = UserToGroup::addInfoToChat(array(
						'group_id' => $groupID,
						'user_id' => $userID,
						'action' => UserToGroup::CHAT_ACTION_OUT
					));

					if (!$chatNotificationResult)
					{
						CSocNetUserToGroup::notifyImToModerators(array(
							"TYPE" => "unjoin",
							"RELATION_ID" => $arResult["ID"],
							"USER_ID" => $userID,
							"GROUP_ID" => $groupID,
							"GROUP_NAME" => $arResult["GROUP_NAME"]
						));
					}
				}
			}
			else
			{
				$errorMessage = "";
				if ($e = $APPLICATION->GetException())
					$errorMessage = $e->GetString();
				if ($errorMessage == '')
					$errorMessage = GetMessage("SONET_UR_ERROR_CREATE_USER2GROUP");

				$APPLICATION->ThrowException($errorMessage, "ERROR_DELETE_RELATION");
				return false;
			}
		}
		else
		{
			$APPLICATION->ThrowException(GetMessage("SONET_NO_USER2GROUP"), "ERROR_NO_MEMBER_REQUEST");
			return false;
		}

		CSocNetUserToGroup::__SpeedFileCheckMessages($userID);

		return true;
	}

	public static function InitUserPerms($userID, $arGroup, $bCurrentUserIsAdmin)
	{
		global $arSocNetAllowedInitiatePerms;
		global $arSocNetAllowedSpamPerms;

		$arReturn = array();

		$userID = intval($userID);
		$groupID = intval($arGroup["ID"]);
		$groupOwnerID = intval($arGroup["OWNER_ID"]);
		$groupInitiatePerms = Trim($arGroup["INITIATE_PERMS"]);
		$groupVisible = Trim($arGroup["VISIBLE"]);
		$groupOpened = Trim($arGroup["OPENED"]);
		$groupSpamPerms = Trim($arGroup["SPAM_PERMS"]);

		if ($groupID <= 0 || $groupOwnerID <= 0 || !in_array($groupInitiatePerms, $arSocNetAllowedInitiatePerms))
			return false;

		$arReturn["Operations"] = array();

		if (!in_array($groupSpamPerms, $arSocNetAllowedSpamPerms))
			$groupSpamPerms = "K";

		// UserRole - User role in group. False if user is not group member.
		// UserIsMember - True in user is group member.
		// UserIsAuto - True in user is group auto member.
		// UserIsOwner - True if user is group owner.
		// UserCanInitiate - True if user can invite friends to group.
		// UserCanViewGroup - True if user can view group.
		// UserCanAutoJoinGroup - True if user can join group automatically.
		// UserCanModifyGroup - True if user can modify group.
		// UserCanModerateGroup - True if user can moderate group.

		if ($userID <= 0)
		{
			$arReturn["UserRole"] = false;
			$arReturn["UserIsMember"] = false;
			$arReturn["UserIsAutoMember"] = false;
			$arReturn["UserIsOwner"] = false;
			$arReturn["UserCanInitiate"] = false;
			$arReturn["UserCanProcessRequestsIn"] = false;
			$arReturn["UserCanViewGroup"] = ($groupVisible == "Y");
			$arReturn["UserCanAutoJoinGroup"] = false;
			$arReturn["UserCanModifyGroup"] = false;
			$arReturn["UserCanModerateGroup"] = false;
			$arReturn["UserCanSpamGroup"] = false;
			$arReturn["InitiatedByType"] = false;
			$arReturn["Operations"]["viewsystemevents"] = false;
		}
		else
		{
			$arUserRoleExtended = CSocNetUserToGroup::GetUserRole($userID, $groupID, true);
			$arReturn["UserRole"] = $arUserRoleExtended["ROLE"];

			$arReturn["UserIsMember"] = (
				$arReturn["UserRole"]
				&& in_array($arReturn["UserRole"], \Bitrix\Socialnetwork\UserToGroupTable::getRolesMember())
			);
			$arReturn["UserIsAutoMember"] = (
				$arReturn["UserIsMember"]
				&& $arUserRoleExtended["AUTO_MEMBER"] == "Y"
			);

			$arReturn["InitiatedByType"] = false;
			if ($arReturn["UserRole"] == SONET_ROLES_REQUEST)
			{
				$dbRelation = CSocNetUserToGroup::GetList(array(), array("USER_ID" => $userID, "GROUP_ID" => $groupID), false, false, array("INITIATED_BY_TYPE"));
				if ($arRelation = $dbRelation->Fetch())
				{
					$arReturn["InitiatedByType"] = $arRelation["INITIATED_BY_TYPE"];
				}
			}

			$arReturn["UserIsOwner"] = ($userID == $groupOwnerID);

			if ($bCurrentUserIsAdmin)
			{
				$arReturn["UserCanInitiate"] = true;
				$arReturn["UserCanProcessRequestsIn"] = true;
				$arReturn["UserCanViewGroup"] = true;
				$arReturn["UserCanAutoJoinGroup"] = true;
				$arReturn["UserCanModifyGroup"] = true;
				$arReturn["UserCanModerateGroup"] = true;
				$arReturn["UserCanSpamGroup"] = true;
				$arReturn["Operations"]["viewsystemevents"] = true;
			}
			else
			{
				if ($arReturn["UserIsMember"])
				{
					$arReturn["UserCanInitiate"] = (
						($groupInitiatePerms == SONET_ROLES_OWNER && $arReturn["UserIsOwner"])
						|| (
							$groupInitiatePerms == SONET_ROLES_MODERATOR
							&& in_array($arReturn["UserRole"], array(SONET_ROLES_OWNER, SONET_ROLES_MODERATOR))
						)
						|| (
							$groupInitiatePerms == SONET_ROLES_USER
							&& $arReturn["UserIsMember"]
						)
					);
					$arReturn["UserCanProcessRequestsIn"] = (
						$arReturn["UserCanInitiate"]
						&& in_array($arReturn["UserRole"], array(SONET_ROLES_OWNER, SONET_ROLES_MODERATOR))
					);
					$arReturn["UserCanViewGroup"] = true;
					$arReturn["UserCanAutoJoinGroup"] = false;
					$arReturn["UserCanModifyGroup"] = $arReturn["UserIsOwner"];
					$arReturn["UserCanModerateGroup"] = (in_array($arReturn["UserRole"], array(SONET_ROLES_OWNER, SONET_ROLES_MODERATOR)));
					$arReturn["UserCanSpamGroup"] = (
						($groupSpamPerms == SONET_ROLES_OWNER && $arReturn["UserIsOwner"])
						|| ($groupSpamPerms == SONET_ROLES_MODERATOR
							&& in_array($arReturn["UserRole"], array(SONET_ROLES_OWNER, SONET_ROLES_MODERATOR)))
						|| ($groupSpamPerms == SONET_ROLES_USER && $arReturn["UserIsMember"])
						|| ($groupSpamPerms == SONET_ROLES_ALL));
					$arReturn["Operations"]["viewsystemevents"] = true;
				}
				else
				{
					$arReturn["UserCanInitiate"] = false;
					$arReturn["UserCanViewGroup"] = ($groupVisible == "Y");
					$arReturn["UserCanAutoJoinGroup"] = ($arReturn["UserCanViewGroup"] && ($groupOpened == "Y"));
					$arReturn["UserCanModifyGroup"] = false;
					$arReturn["UserCanModerateGroup"] = false;
					$arReturn["UserCanSpamGroup"] = ($groupSpamPerms == SONET_ROLES_ALL);
					$arReturn["Operations"]["viewsystemevents"] = false;
				}
			}
		}

		if (CModule::IncludeModule('extranet') && CExtranet::IsExtranetSite())
		{
			$arReturn["UserCanSpamGroup"] = true;
		}

		if (!CBXFeatures::IsFeatureEnabled("WebMessenger"))
		{
			$arReturn["UserCanSpamGroup"] = false;
		}

		return $arReturn;
	}

	public static function __SpeedFileCheckMessages($userID)
	{
		global $DB;

		$userID = intval($userID);
		if ($userID <= 0)
			return;

		$cnt = 0;
		$dbResult = $DB->Query(
			"SELECT COUNT(ID) as CNT ".
			"FROM b_sonet_user2group ".
			"WHERE USER_ID = ".$userID." ".
			"	AND ROLE = '".$DB->ForSql(SONET_ROLES_REQUEST, 1)."' ".
			"	AND INITIATED_BY_TYPE = '".$DB->ForSql(SONET_INITIATED_BY_GROUP, 1)."' "
		);
		if ($arResult = $dbResult->Fetch())
			$cnt = intval($arResult["CNT"]);

		if ($cnt > 0)
			CSocNetUserToGroup::__SpeedFileCreate($userID);
		else
			CSocNetUserToGroup::__SpeedFileDelete($userID);
	}

	public static function __SpeedFileCreate($userID)
	{
		global $CACHE_MANAGER;

		$userID = intval($userID);
		if ($userID <= 0)
			return;

		if ($CACHE_MANAGER->Read(86400*30, "socnet_cg_".$userID))
			$CACHE_MANAGER->Clean("socnet_cg_".$userID);
	}

	public static function __SpeedFileDelete($userID)
	{
		global $CACHE_MANAGER;

		$userID = intval($userID);
		if ($userID <= 0)
			return;

		if (!$CACHE_MANAGER->Read(86400*30, "socnet_cg_".$userID))
			$CACHE_MANAGER->Set("socnet_cg_".$userID, true);
	}

	function SpeedFileExists($userID)
	{
		global $CACHE_MANAGER;

		$userID = intval($userID);
		if ($userID <= 0)
			return false;

		return (!$CACHE_MANAGER->Read(86400*30, "socnet_cg_".$userID));
	}

	/* Module IM callback */
	function OnBeforeConfirmNotify($module, $tag, $value, $arParams)
	{
		global $USER;

		if ($module == "socialnetwork")
		{
			$arTag = explode("|", $tag);
			if (count($arTag) == 4 && $arTag[1] == 'INVITE_GROUP')
			{
				if ($value == 'Y')
				{
					self::UserConfirmRequestToBeMember($arTag[2], $arTag[3]);
					return true;
				}
				else
				{
					self::UserRejectRequestToBeMember($arTag[2], $arTag[3]);
					return true;
				}
			}
			elseif (count($arTag) == 6 && $arTag[1] == "REQUEST_GROUP")
			{
				if ($value == "Y")
				{
					self::ConfirmRequestToBeMember($USER->GetID(), $arTag[3], array($arTag[4]));
				}
				else
				{
					self::RejectRequestToBeMember($USER->GetID(), $arTag[3], array($arTag[4]));
				}

				if (CModule::IncludeModule('im'))
				{
					CIMNotify::DeleteBySubTag("SOCNET|REQUEST_GROUP|".$arTag[2]."|".$arTag[3]."|".$arTag[4]);
				}

				return true;
			}
		}
	}

	public static function NotifyImToModerators($arNotifyParams)
	{
		if (!CModule::IncludeModule("im"))
		{
			return;
		}

		if (
			!is_array($arNotifyParams)
			|| !array_key_exists("TYPE", $arNotifyParams)
			|| !in_array($arNotifyParams["TYPE"], array("join", "unjoin", "exclude", "moderate", "unmoderate", "owner"))
			|| !array_key_exists("USER_ID", $arNotifyParams)
			|| intval($arNotifyParams["USER_ID"]) <= 0
			|| !array_key_exists("GROUP_ID", $arNotifyParams)
			|| intval($arNotifyParams["GROUP_ID"]) <= 0
			|| !array_key_exists("RELATION_ID", $arNotifyParams)
			|| intval($arNotifyParams["RELATION_ID"]) <= 0
			|| !array_key_exists("GROUP_NAME", $arNotifyParams)
			|| $arNotifyParams["GROUP_NAME"] == ''
		)
		{
			return;
		}

		$from_user_id = $message_code = $schema_code = $notify_tag = false;

		switch ($arNotifyParams["TYPE"])
		{
			case "join":
				$from_user_id = $arNotifyParams["USER_ID"];
				$message_code = "SONET_UG_IM_JOIN";
				$schema_code = "inout_group";
				$notify_tag = "INOUT_GROUP";
				break;
			case "unjoin":
				$from_user_id = $arNotifyParams["USER_ID"];
				$message_code = "SONET_UG_IM_UNJOIN";
				$schema_code = "inout_group";
				$notify_tag = "INOUT_GROUP";
				break;
			case "exclude":
				$from_user_id = $arNotifyParams["USER_ID"];
				$message_code = "SONET_UG_IM_EXCLUDE";
				$schema_code = "inout_group";
				$notify_tag = "INOUT_GROUP";
				break;
			case "moderate":
				$from_user_id = $arNotifyParams["USER_ID"];
				$message_code = "SONET_UG_IM_MODERATE";
				$schema_code = "moderators_group";
				$notify_tag = "MOD_GROUP";
				break;
			case "unmoderate":
				$from_user_id = $arNotifyParams["USER_ID"];
				$message_code = "SONET_UG_IM_UNMODERATE";
				$schema_code = "moderators_group";
				$notify_tag = "MOD_GROUP";
				break;
			case "owner":
				$from_user_id = $arNotifyParams["USER_ID"];
				$message_code = "SONET_UG_IM_OWNER";
				$schema_code = "owner_group";
				$notify_tag = "OWNER_GROUP";
				break;
			default:
		}

		$gender_suffix = "";
		$rsUser = CUser::GetByID($arNotifyParams["USER_ID"]);
		if ($arUser = $rsUser->Fetch())
		{
			switch ($arUser["PERSONAL_GENDER"])
			{
				case "M":
					$gender_suffix = "_M";
					break;
				case "F":
					$gender_suffix = "_F";
					break;
				default:
					$gender_suffix = "";
			}
		}

		$arToUserID = array();

		$rsUserToGroup = CSocNetUserToGroup::GetList(
			array(),
			array(
				"GROUP_ID" => $arNotifyParams["GROUP_ID"],
				"USER_ACTIVE" => "Y",
				"<=ROLE" => SONET_ROLES_MODERATOR
			),
			false,
			false,
			array("USER_ID")
		);
		while ($arUserToGroup = $rsUserToGroup->Fetch())
		{
			$arToUserID[] = $arUserToGroup["USER_ID"];
		}

		$arMessageFields = array(
			"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
			"FROM_USER_ID" => $from_user_id,
			"NOTIFY_TYPE" => IM_NOTIFY_FROM,
			"NOTIFY_MODULE" => "socialnetwork",
			"NOTIFY_EVENT" => $schema_code,
			"NOTIFY_TAG" => "SOCNET|".$notify_tag."|".intval($arNotifyParams["USER_ID"])."|".intval($arNotifyParams["GROUP_ID"])."|".intval($arNotifyParams["RELATION_ID"]),
		);

		$groups_path = COption::GetOptionString("socialnetwork", "workgroups_page", SITE_DIR."workgroups/");
		$group_url_template = str_replace(
			$groups_path, 
			"#GROUPS_PATH#", 
			COption::GetOptionString("socialnetwork", "group_path_template", SITE_DIR."workgroups/group/#group_id#/")
		);

		$groupUrl = str_replace(
			"#group_id#", 
			$arNotifyParams["GROUP_ID"], 
			$group_url_template
		);

		foreach($arToUserID as $to_user_id)
		{
			if (
				(
					is_array($arNotifyParams["EXCLUDE_USERS"])
					&& in_array($to_user_id, $arNotifyParams["EXCLUDE_USERS"])
				)
				|| $to_user_id == $from_user_id
			)
			{
				continue;
			}

			$arMessageFields["TO_USER_ID"] = $to_user_id;
			$arTmp = CSocNetLogTools::ProcessPath(
				array(
					"GROUP_PAGE" => $groupUrl
				), 
				$to_user_id, 
				SITE_ID
			);

			$arMessageFields["NOTIFY_MESSAGE"] = GetMessage($message_code.$gender_suffix, Array(
				"#group_name#" => "<a href=\"".$arTmp["URLS"]["GROUP_PAGE"]."\" class=\"bx-notifier-item-action\">".$arNotifyParams["GROUP_NAME"]."</a>",
			));

			$arMessageFields["NOTIFY_MESSAGE_OUT"] = GetMessage($message_code.$gender_suffix, Array(
				"#group_name#" => $arNotifyParams["GROUP_NAME"],
			))." (".$arTmp["SERVER_NAME"].$arTmp["URLS"]["GROUP_PAGE"].")";

			CIMNotify::Add($arMessageFields);
		}
	}

	public static function getMessage($message)
	{
		return Loc::getMessage($message);
	}

	public static function notifyModeratorAdded($params)
	{
		static $groupCache = array();

		$userId = (!empty($params['userId']) ? intval($params['userId']) : 0);
		$relationFields = (!empty($params['relationFields']) && is_array($params['relationFields']) ? $params['relationFields'] : array());
		$groupFields = (!empty($params['groupFields']) && is_array($params['groupFields']) ? $params['groupFields'] : array());
		$groupId = (
			!empty($params['groupId'])
				? intval($params['groupId'])
				: (!empty($groupFields['ID']) ? intval($groupFields['ID']) : 0)
		);
		$relationId = (
			!empty($params['relationId'])
				? intval($params['relationId'])
				: (!empty($relationFields['ID']) ? intval($relationFields['ID']) : 0)
		);

		if (
			empty($groupFields)
			&& $groupId > 0
		)
		{
			if (isset($groupCache[$groupId]))
			{
				$groupFields = $groupCache[$groupId];
			}
			else
			{
				$res = \Bitrix\Socialnetwork\WorkgroupTable::getList(array(
					'filter' => array(
						'=ID' => $groupId
					),
					'select' => array('ID', 'NAME', 'SITE_ID')
				));
				$groupFields = $groupCache[$groupId] = $res->fetch();
			}
		}

		if (
			empty($relationFields)
			&& $relationId > 0
		)
		{
			$res = \Bitrix\Socialnetwork\UserToGroupTable::getList(array(
				'filter' => array(
					'=ID' => $relationId
				),
				'select' => array('ID', 'USER_ID')
			));
			$relationFields = $res->fetch();
		}

		if (
			$groupId <= 0
			|| empty($relationFields)
			|| empty($relationFields['ID'])
			|| empty($relationFields['USER_ID'])
			|| empty($groupFields)
			|| !Loader::includeModule('im')
		)
		{
			return;
		}

		$groupSiteId = CSocNetGroup::getDefaultSiteId($groupId, $groupFields["SITE_ID"]);

		$workgroupsPage = COption::getOptionString("socialnetwork", "workgroups_page", "/workgroups/", SITE_ID);
		$groupUrlTemplate = COption::getOptionString("socialnetwork", "group_path_template", "/workgroups/group/#group_id#/", SITE_ID);
		$groupUrlTemplate = "#GROUPS_PATH#".mb_substr($groupUrlTemplate, mb_strlen($workgroupsPage), mb_strlen($groupUrlTemplate) - mb_strlen($workgroupsPage));
		$groupUrl = str_replace(array("#group_id#", "#GROUP_ID#"), $groupId, $groupUrlTemplate);

		$arTmp = CSocNetLogTools::processPath(
			array(
				"GROUP_URL" => $groupUrl
			),
			$relationFields["USER_ID"],
			$groupSiteId
		);
		$groupUrl = $arTmp["URLS"]["GROUP_URL"];

		$serverName = (
		mb_strpos($groupUrl, "http://") === 0
			|| mb_strpos($groupUrl, "https://") === 0
				? ""
				: $arTmp["SERVER_NAME"]
		);
		$domainName = (
		mb_strpos($groupUrl, "http://") === 0
			|| mb_strpos($groupUrl, "https://") === 0
				? ""
				: (
					isset($arTmp["DOMAIN"])
					&& !empty($arTmp["DOMAIN"])
						? "//".$arTmp["DOMAIN"]
						: ""
				)
		);

		$arMessageFields = array(
			"TO_USER_ID" => $relationFields["USER_ID"],
			"FROM_USER_ID" => $userId,
			"NOTIFY_TYPE" => IM_NOTIFY_FROM,
			"NOTIFY_MODULE" => "socialnetwork",
			"NOTIFY_EVENT" => "moderators_group",
			"NOTIFY_TAG" => "SOCNET|MOD_GROUP|".intval($userId)."|".$groupId."|".$relationFields["ID"]."|".$relationFields["USER_ID"],
			"NOTIFY_MESSAGE" => str_replace(
				array("#NAME#"),
				array("<a href=\"".$domainName.$groupUrl."\" class=\"bx-notifier-item-action\">".$groupFields["NAME"]."</a>"),
				GetMessage("SONET_UG_MEMBER2MOD_MESSAGE")
			),
			"NOTIFY_MESSAGE_OUT" => str_replace(
					array("#NAME#"),
					array($groupFields["NAME"]),
					Loc::getMessage("SONET_UG_MEMBER2MOD_MESSAGE")
				)." (".$serverName.$groupUrl.")"
		);

		CIMNotify::add($arMessageFields);
	}

}
?>