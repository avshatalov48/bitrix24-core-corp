<?
define("NO_KEEP_STATISTIC", true);
define("BX_STATISTIC_BUFFER_USED", false);
define("NO_LANG_FILES", true);
define("NOT_CHECK_PERMISSIONS", true);
define("BX_PUBLIC_TOOLS", true);

$site_id = isset($_REQUEST["site"]) && is_string($_REQUEST["site"]) ? trim($_REQUEST["site"]) : "";
$site_id = mb_substr(preg_replace("/[^a-z0-9_]/i", "", $site_id), 0, 2);

define("SITE_ID", $site_id);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/bx_root.php");

$action = isset($_REQUEST["action"]) && is_string($_REQUEST["action"]) ? trim($_REQUEST["action"]) : "";

$lng = isset($_REQUEST["lang"]) && is_string($_REQUEST["lang"]) ? trim($_REQUEST["lang"]) : "";
$lng = mb_substr(preg_replace("/[^a-z0-9_]/i", "", $lng), 0, 2);

$ls = isset($_REQUEST["ls"]) && is_string($_REQUEST["ls"]) ? trim($_REQUEST["ls"]) : "";
$ls_arr = isset($_REQUEST["ls_arr"])? $_REQUEST["ls_arr"]: "";

$as = isset($_REQUEST["as"]) ? intval($_REQUEST["as"]) : 58;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$rsSite = CSite::GetByID($site_id);
if ($arSite = $rsSite->Fetch())
	define("LANGUAGE_ID", $arSite["LANGUAGE_ID"]);
else
	define("LANGUAGE_ID", "en");

$APPLICATION->IncludeComponent("bitrix:mobile.data", "", Array(
		"START_PAGE" => "/mobile/index.php",
		"MENU_PAGE" => "/mobile/left.php"
	),
	false,
	Array("HIDE_ICONS" => "Y")
);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/mobile.socialnetwork.log/include.php");

__IncludeLang(__DIR__."/lang/".$lng."/ajax.php");

if(CModule::IncludeModule("socialnetwork"))
{
	$bCurrentUserIsAdmin = CSocNetUser::IsCurrentUserModuleAdmin();

	// write and close session to prevent lock;
	session_write_close();

	$arResult = array();

/*
	if (in_array($action, array("get_comment", "get_comments")))
	{
		$GLOBALS["arExtranetGroupID"] = array();
		$GLOBALS["arExtranetUserID"] = array();

		if ($GLOBALS["USER"]->IsAuthorized())
		{
			if(defined("BX_COMP_MANAGED_CACHE"))
				$ttl = 2592000;
			else
				$ttl = 600;

			$cache_id = 'sonet_ex_gr_'.SITE_ID;
			$obCache = new CPHPCache;
			$cache_dir = '/bitrix/sonet_log_sg';

			if($obCache->InitCache($ttl, $cache_id, $cache_dir))
			{
				$tmpVal = $obCache->GetVars();
				$GLOBALS["arExtranetGroupID"] = $tmpVal['EX_GROUP_ID'];
				$GLOBALS["arExtranetUserID"] = $tmpVal['EX_USER_ID'];
				unset($tmpVal);
			}
			elseif (CModule::IncludeModule("extranet") && !CExtranet::IsExtranetSite())
			{
				global $CACHE_MANAGER;
				$CACHE_MANAGER->StartTagCache($cache_dir);

				$dbGroupTmp = CSocNetGroup::GetList(
					array(),
					array(
						"SITE_ID" => CExtranet::GetExtranetSiteID()
					),
					false,
					false,
					array("ID")
				);
				while($arGroupTmp = $dbGroupTmp->Fetch())
				{
					$GLOBALS["arExtranetGroupID"][] = $arGroupTmp["ID"];
					$CACHE_MANAGER->RegisterTag('sonet_group_'.$arGroupTmp["ID"]);
				}

				$rsUsers = CUser::GetList(
					($by="ID"),
					($order="asc"),
					array(
						"GROUPS_ID" => array(CExtranet::GetExtranetUserGroupID()),
						"UF_DEPARTMENT" => false
					)
				);
				while($arUser = $rsUsers->Fetch())
				{
					$GLOBALS["arExtranetUserID"][] = $arUser["ID"];
					$CACHE_MANAGER->RegisterTag('sonet_user2group_U'.$arUser["ID"]);
				}

				$CACHE_MANAGER->EndTagCache();
				if($obCache->StartDataCache())
					$obCache->EndDataCache(array(
						'EX_GROUP_ID' => $GLOBALS["arExtranetGroupID"],
						'EX_USER_ID' => $GLOBALS["arExtranetUserID"]
					));
			}
			unset($obCache);
		}
	}
*/

	if (!$GLOBALS["USER"]->IsAuthorized())
		$arResult[0] = "*";
	elseif (!check_bitrix_sessid())
		$arResult[0] = "*";
	elseif ($action == "add_comment")
	{
		$log_id = $_REQUEST["log_id"];
		if ($arLog = CSocNetLog::GetByID($log_id))
		{
			$arCommentEvent = CSocNetLogTools::FindLogCommentEventByLogEventID($arLog["EVENT_ID"]);
			if ($arCommentEvent)
			{
				$feature = CSocNetLogTools::FindFeatureByEventID($arCommentEvent["EVENT_ID"]);

				if (
					array_key_exists("OPERATION_ADD", $arCommentEvent) 
					&& $arCommentEvent["OPERATION_ADD"] == "log_rights"
				)
					$bCanAddComments = CSocNetLogRights::CheckForUser($log_id, $GLOBALS["USER"]->GetID());
				elseif (
					$feature 
					&& array_key_exists("OPERATION_ADD", $arCommentEvent) 
					&& $arCommentEvent["OPERATION_ADD"] <> ''
				)
					$bCanAddComments = CSocNetFeaturesPerms::CanPerformOperation(
						$GLOBALS["USER"]->GetID(), 
						$arLog["ENTITY_TYPE"], 
						$arLog["ENTITY_ID"], 
						($feature == "microblog" ? "blog" : $feature), 
						$arCommentEvent["OPERATION_ADD"], 
						$bCurrentUserIsAdmin
					);
				else
					$bCanAddComments = true;

				if ($bCanAddComments)
				{
					// add source object and get source_id, $source_url
					$arParams = array(
						"PATH_TO_SMILE" => $_REQUEST["p_smile"],
						"PATH_TO_USER_BLOG_POST" => $_REQUEST["p_ubp"],
						"PATH_TO_GROUP_BLOG_POST" => $_REQUEST["p_gbp"],
						"PATH_TO_USER_MICROBLOG_POST" => $_REQUEST["p_umbp"],
						"PATH_TO_GROUP_MICROBLOG_POST" => $_REQUEST["p_gmbp"],
						"BLOG_ALLOW_POST_CODE" => $_REQUEST["bapc"]
					);
					$parser = new logTextParser(LANGUAGE_ID, $arParams["PATH_TO_SMILE"]);

					$comment_text = $_REQUEST["message"];
					CUtil::decodeURIComponent($comment_text);
					$comment_text = Trim($comment_text);

					if ($comment_text <> '')
					{
						$arAllow = array(
							"HTML" => "N",
							"ANCHOR" => "Y",
							"LOG_ANCHOR" => "N",
							"BIU" => "N",
							"IMG" => "N",
							"LIST" => "N",
							"QUOTE" => "N",
							"CODE" => "N",
							"FONT" => "N",
							"UPLOAD" => $arForum["ALLOW_UPLOAD"],
							"NL2BR" => "N",
							"SMILES" => "N"
						);

						$arFields = array(
							"ENTITY_TYPE" => $arLog["ENTITY_TYPE"],
							"ENTITY_ID" => $arLog["ENTITY_ID"],
							"EVENT_ID" => $arCommentEvent["EVENT_ID"],
							"=LOG_DATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
							"MESSAGE" => $parser->convert($comment_text, array(), $arAllow),
							"TEXT_MESSAGE" => $comment_text,
							"URL" => $source_url,
							"MODULE_ID" => false,
							"SOURCE_ID" => $source_id,
							"LOG_ID" => $arLog["TMP_ID"],
							"USER_ID" => $GLOBALS["USER"]->GetID(),
							"PATH_TO_USER_BLOG_POST" => $arParams["PATH_TO_USER_BLOG_POST"],
							"PATH_TO_GROUP_BLOG_POST" => $arParams["PATH_TO_GROUP_BLOG_POST"],
							"PATH_TO_USER_MICROBLOG_POST" => $arParams["PATH_TO_USER_MICROBLOG_POST"],
							"PATH_TO_GROUP_MICROBLOG_POST" => $arParams["PATH_TO_GROUP_MICROBLOG_POST"],
							"BLOG_ALLOW_POST_CODE" => $arParams["BLOG_ALLOW_POST_CODE"]
						);

						$comment = CSocNetLogComments::Add($arFields, true);
						if (!is_array($comment) && intval($comment) > 0)
							$arResult["commentID"] = $comment;
						elseif (is_array($comment) &&  array_key_exists("MESSAGE", $comment) && $comment["MESSAGE"] <> '')
						{
							$arResult["strMessage"] = $comment["MESSAGE"];
							$arResult["commentText"] = $comment_text;
						}
					}
					else
						$arResult["strMessage"] = GetMessage("SONET_LOG_COMMENT_EMPTY");
				}
				else
					$arResult["strMessage"] = GetMessage("SONET_LOG_COMMENT_NO_PERMISSIONS");
			}
		}
	}
	elseif ($action == "get_comment")
	{
		$comment_id = $_REQUEST["cid"];

		if ($arComment = CSocNetLogComments::GetByID($comment_id))
		{
			$arParams["DATE_TIME_FORMAT"] = $_REQUEST["dtf"];

			$dateFormated = FormatDate(
				$GLOBALS['DB']->DateFormatToPHP(FORMAT_DATE),
				MakeTimeStamp($arComment["LOG_DATE"])
			);
			$timeFormated = FormatDateFromDB($arComment["LOG_DATE"], (mb_stripos($arParams["DATE_TIME_FORMAT"], 'a') || ($arParams["DATE_TIME_FORMAT"] == 'FULL' && IsAmPmMode()) !== false ? 'H:MI T' : 'HH:MI'));
			$dateTimeFormated = FormatDate(
				(!empty($arParams['DATE_TIME_FORMAT']) ? ($arParams['DATE_TIME_FORMAT'] == 'FULL' ? $GLOBALS['DB']->DateFormatToPHP(str_replace(':SS', '', FORMAT_DATETIME)) : $arParams['DATE_TIME_FORMAT']) : $GLOBALS['DB']->DateFormatToPHP(FORMAT_DATETIME)),
				MakeTimeStamp($arComment["LOG_DATE"])
			);
			if (strcasecmp(LANGUAGE_ID, 'EN') !== 0 && strcasecmp(LANGUAGE_ID, 'DE') !== 0)
			{
				$dateFormated = ToLower($dateFormated);
				$dateTimeFormated = ToLower($dateTimeFormated);
			}
			// strip current year
			if (!empty($arParams['DATE_TIME_FORMAT']) && ($arParams['DATE_TIME_FORMAT'] == 'j F Y G:i' || $arParams['DATE_TIME_FORMAT'] == 'j F Y g:i a'))
			{
				$dateTimeFormated = ltrim($dateTimeFormated, '0');
				$curYear = date('Y');
				$dateTimeFormated = str_replace(array('-'.$curYear, '/'.$curYear, ' '.$curYear, '.'.$curYear), '', $dateTimeFormated);
			}

			if (intval($arComment["USER_ID"]) > 0)
			{
				$arParams = array(
					"PATH_TO_USER" => $_REQUEST["p_user"],
					"NAME_TEMPLATE" => $_REQUEST["nt"],
					"SHOW_LOGIN" => $_REQUEST["sl"],
					"AVATAR_SIZE" => $as,
					"PATH_TO_SMILE" => $_REQUEST["p_smile"]
				);

				$arUser = array(
					"ID" => $arComment["USER_ID"],
					"NAME" => $arComment["~CREATED_BY_NAME"],
					"LAST_NAME" => $arComment["~CREATED_BY_LAST_NAME"],
					"SECOND_NAME" => $arComment["~CREATED_BY_SECOND_NAME"],
					"LOGIN" => $arComment["~CREATED_BY_LOGIN"],
					"PERSONAL_PHOTO" => $arComment["~CREATED_BY_PERSONAL_PHOTO"],
					"PERSONAL_GENDER" => $arComment["~CREATED_BY_PERSONAL_GENDER"],
				);
				$bUseLogin = $arParams["SHOW_LOGIN"] != "N" ? true : false;
				$arCreatedBy = array(
					"FORMATTED" => CUser::FormatName($arParams["NAME_TEMPLATE"], $arUser, $bUseLogin),
					"URL" => CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER"], array("user_id" => $arComment["USER_ID"], "id" => $arComment["USER_ID"]))
				);

			}
			else
				$arCreatedBy = array("FORMATTED" => GetMessage("SONET_LOG_CREATED_BY_ANONYMOUS"));

			$arTmpCommentEvent = array(
				"LOG_DATE" => $arComment["LOG_DATE"],
				"LOG_DATE_FORMAT" => $arComment["LOG_DATE_FORMAT"],
				"LOG_DATE_DAY" => ConvertTimeStamp(MakeTimeStamp($arComment["LOG_DATE"]), "SHORT"),
				"LOG_TIME_FORMAT" => $timeFormated,
				"MESSAGE" => $arComment["MESSAGE"],
				"MESSAGE_FORMAT" => $arComment["~MESSAGE"],
				"CREATED_BY" => $arCreatedBy,
				"AVATAR_SRC" => CSocNetLogTools::FormatEvent_CreateAvatar($arUser, $arParams, ""),
				"USER_ID" => $arComment["USER_ID"]
			);

			$arEventTmp = CSocNetLogTools::FindLogCommentEventByID($arComment["EVENT_ID"]);
			if (
				$arEventTmp
				&& array_key_exists("CLASS_FORMAT", $arEventTmp)
				&& array_key_exists("METHOD_FORMAT", $arEventTmp)
			)
			{
				$arFIELDS_FORMATTED = call_user_func(array($arEventTmp["CLASS_FORMAT"], $arEventTmp["METHOD_FORMAT"]), $arComment, $arParams);
				$arTmpCommentEvent["MESSAGE_FORMAT"] = htmlspecialcharsback($arFIELDS_FORMATTED["EVENT_FORMATTED"]["MESSAGE"]);
			}

			$arResult["arCommentFormatted"] = $arTmpCommentEvent;
		}
	}
	elseif ($action == "get_comments")
	{
		$arResult["arComments"] = array();

		$log_tmp_id = $_REQUEST["logid"];
		$last_comment_id = $_REQUEST["last_comment_id"];

		if (intval($log_tmp_id) > 0)
		{
			$arParams = array(
				"PATH_TO_USER" => $_REQUEST["p_user"],
				"NAME_TEMPLATE" => $_REQUEST["nt"],
				"SHOW_LOGIN" => $_REQUEST["sl"],
				"AVATAR_SIZE_COMMENT" => $as,
				"PATH_TO_SMILE" => $_REQUEST["p_smile"]
			);

			$arFilter = array(
				"LOG_ID" => $log_tmp_id
			);
			if (intval($last_comment_id) > 0)
				$arFilter["<ID"] = $last_comment_id;			
			
			$arListParams = array("USE_SUBSCRIBE" => "N");

			$dbComments = CSocNetLogComments::GetList(
				array("LOG_DATE" => "ASC"),
				$arFilter,
				false,
				false,
				array(),
				$arListParams
			);

			while($arComments = $dbComments->GetNext())
				__SLMGetLogCommentRecord($arComments, $arParams, false, false, $arTmpComments, false);

			$arResult["arComments"] = $arTmpComments;
		}
	}
/*
	elseif ($action == "get_new_posts")
	{
		$arResult["arPosts"] = array();

		$arFilter = array(
			">=LOG_UPDATE" => ConvertTimeStamp($_SESSION["LAST_LOAD_TS"] + CTimeZone::GetOffset(), "FULL"),
			"<=LOG_DATE" => "NOW",
			"SITE_ID" => array(SITE_ID, false)
		);

		$group_id = isset($_REQUEST["group_id"]) ? intval($_REQUEST["group_id"]) : false;
		if ($group_id > 0)
			$arFilter["LOG_RIGHTS"] = "SG".$group_id;

		$arListParams = array(
			"CHECK_RIGHTS" => "Y",
			"USE_SUBSCRIBE" => "N"
		);

		$rsLog = CSocNetLog::GetList(
			array("LOG_UPDATE" => "DESC"),
			$arFilter,
			false,
			false,
			array(),
			$arListParams
		);

		$arParams = array(
			"PATH_TO_USER" => $_REQUEST["p_user"],
			"NAME_TEMPLATE" => $_REQUEST["nt"],
			"SHOW_LOGIN" => $_REQUEST["sl"],
			"AVATAR_SIZE" => $as
		);
		
		while($arLog = $rsLog->GetNext())
			__SLMGetLogRecord($arLog, $arParams, false, false, $arTmpEventsNew);

		$arResult["arPosts"] = $arTmpEventsNew;
		$_SESSION["LAST_LOAD_TS"] = time();
	}
*/
	elseif ($action == "change_favorites")
	{
		$log_id = $_REQUEST["log_id"];
		if ($arLog = CSocNetLog::GetByID($log_id))
		{
			if ($strRes = CSocNetLogFavorites::Change($GLOBALS["USER"]->GetID(), $log_id))
			{
				$arResult["bResult"] = $strRes;
				if ($strRes == "Y")
				{
					if (method_exists('\Bitrix\Socialnetwork\ComponentHelper','userLogSubscribe'))
					{
						\Bitrix\Socialnetwork\ComponentHelper::userLogSubscribe(array(
							'logId' => $log_id,
							'userId' => $GLOBALS["USER"]->GetID(),
							'typeList' => array(
								'FOLLOW',
								'COUNTER_COMMENT_PUSH'
							)
						));
					}
					else
					{
						CSocNetLogFollow::Set($GLOBALS["USER"]->GetID(), "L".$log_id, "Y");
					}
				}
			}
			else
			{
				if($e = $GLOBALS["APPLICATION"]->GetException())
					$arResult["strMessage"] = $e->GetString();
				else
					$arResult["strMessage"] = GetMessage("SONET_LOG_FAVORITES_CANNOT_CHANGE");
				$arResult["bResult"] = "E";
			}
		}
		else
		{
			$arResult["strMessage"] = GetMessage("SONET_LOG_FAVORITES_INCORRECT_LOG_ID");
			$arResult["bResult"] = "E";
		}
	}
	elseif ($action == "change_follow")
	{
		$log_id = intval($_REQUEST["log_id"]);

		if ($log_id > 0)
		{
			if (
				$_REQUEST["follow"] == "Y"
				&& method_exists('\Bitrix\Socialnetwork\ComponentHelper','userLogSubscribe')
			)
			{
				$strRes = \Bitrix\Socialnetwork\ComponentHelper::userLogSubscribe(array(
					'logId' => $log_id,
					'userId' => $GLOBALS["USER"]->GetID(),
					'typeList' => array(
						'FOLLOW',
						'COUNTER_COMMENT_PUSH'
					)
				));
			}
			else
			{
				$strRes = CSocNetLogFollow::Set($GLOBALS["USER"]->GetID(), "L".$log_id, ($_REQUEST["follow"] == "Y" ? "Y" : "N"));
			}
			$arResult["SUCCESS"] = ($strRes ? "Y" : "N");
		}
		else
		{
			$arResult["SUCCESS"] = "N";
		}
	}
	elseif ($action == "get_more_destination")
	{
		$arResult["arDestinations"] = false;
		$log_id = intval($_REQUEST["log_id"]);
		$author_id = intval($_REQUEST["author_id"]);
		$iDestinationLimit = intval($_REQUEST["dlim"]);

		if ($log_id > 0)
		{
			$dbRight = CSocNetLogRights::GetList(array(), array("LOG_ID" => $log_id));
			while ($arRight = $dbRight->Fetch())
				$arRights[] = $arRight["GROUP_CODE"];

			$arParams = array(
				"PATH_TO_USER" => $_REQUEST["p_user"],
				"PATH_TO_GROUP" => $_REQUEST["p_group"],
				"PATH_TO_CONPANY_DEPARTMENT" => $_REQUEST["p_dep"],
				"NAME_TEMPLATE" => $_REQUEST["nt"],
				"SHOW_LOGIN" => $_REQUEST["sl"],
				"DESTINATION_LIMIT" => 100
			);

			$arDestinations = CSocNetLogTools::FormatDestinationFromRights($arRights, array_merge($arParams, array("CREATED_BY" => $author_id)), $iMoreCount);
			if (is_array($arDestinations))
				$arResult["arDestinations"] = array_slice($arDestinations, $iDestinationLimit);
		}
	}
	elseif ($action == "get_more_destination")
	{
		$arResult["arDestinations"] = false;
		$log_id = intval($_REQUEST["log_id"]);
		$iDestinationLimit = intval($_REQUEST["dlim"]);

		if ($log_id > 0)
		{
			$dbRight = CSocNetLogRights::GetList(array(), array("LOG_ID" => $log_id));
			while ($arRight = $dbRight->Fetch())
				$arRights[] = $arRight["GROUP_CODE"];

			$arParams = array(
				"PATH_TO_USER" => $_REQUEST["p_user"],
				"PATH_TO_GROUP" => $_REQUEST["p_group"],
				"PATH_TO_CONPANY_DEPARTMENT" => $_REQUEST["p_dep"],
				"NAME_TEMPLATE" => $_REQUEST["nt"],
				"SHOW_LOGIN" => $_REQUEST["sl"],
				"DESTINATION_LIMIT" => 100
			);

			$arDestinations = CSocNetLogTools::FormatDestinationFromRights($arRights, $arParams, $iMoreCount);
			if (is_array($arDestinations))
				$arResult["arDestinations"] = array_slice($arDestinations, $iDestinationLimit);
		}
	}

	header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
	echo CUtil::PhpToJSObject($arResult);
}

define('PUBLIC_AJAX_MODE', true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>