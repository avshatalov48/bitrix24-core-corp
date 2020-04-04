<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();
$arParams["TASK_ID"] = intVal(intVal($arParams["TASK_ID"]) <= 0 ? $GLOBALS["ID"] : $arParams["TASK_ID"]);
$arParams["TASK_ID"] = intVal(intVal($arParams["TASK_ID"]) <= 0 ? $_REQUEST["TASK_ID"] : $arParams["TASK_ID"]);

if (!CModule::IncludeModule("forum"))
{
	ShowError(GetMessage("F_NO_MODULE"));
	return 0;
}
elseif (!CModule::IncludeModule("tasks"))
{
	ShowError(GetMessage("F_NO_MODULE_TASKS"));
	return 0;
}
elseif (intVal($arParams["FORUM_ID"]) <= 0)
{
	ShowError(GetMessage("F_ERR_FID_EMPTY"));
	return 0;
}
elseif (intVal($arParams["TASK_ID"]) <= 0)
{
	ShowError(GetMessage("F_ERR_TID_EMPTY"));
	return 0;
}
/* * ******************************************************************
	Input params
 * ****************************************************************** */
/* * *************** BASE ******************************************* */
$arParams["FORUM_ID"] = intVal($arParams["FORUM_ID"]);
$arParams["TASK_ID"] = intVal($arParams["TASK_ID"]);
/* * *************** URL ******************************************** */
$URL_NAME_DEFAULT = array(
	"read" => "PAGE_NAME=read&FID=#FID#&TID=#TID#&MID=#MID#",
	"profile_view" => "PAGE_NAME=profile_view&UID=#UID#",
	"detail" => "PAGE_NAME=detail&SECTION_ID=#SECTION_ID#&ELEMENT_ID=#ELEMENT_ID#"
);
foreach ($URL_NAME_DEFAULT as $URL => $URL_VALUE)
{
	if (empty($arParams["URL_TEMPLATES_".strToUpper($URL)]))
	{
		continue;
	}
	$arParams["~URL_TEMPLATES_".strToUpper($URL)] = $arParams["URL_TEMPLATES_".strToUpper($URL)];
	$arParams["URL_TEMPLATES_".strToUpper($URL)] = htmlspecialcharsbx($arParams["~URL_TEMPLATES_".strToUpper($URL)]);
}
/* * *************** ADDITIONAL ************************************* */
$arParams["POST_FIRST_MESSAGE"] = (isset($arParams["POST_FIRST_MESSAGE"]) && $arParams["POST_FIRST_MESSAGE"] == "Y" ? "Y" : "N");
$arParams["POST_FIRST_MESSAGE_TEMPLATE"] = isset($arParams["POST_FIRST_MESSAGE_TEMPLATE"]) ? trim($arParams["POST_FIRST_MESSAGE_TEMPLATE"]) : "";
if (empty($arParams["POST_FIRST_MESSAGE_TEMPLATE"]))
{
	$arParams["POST_FIRST_MESSAGE_TEMPLATE"] = "#IMAGE# \n [url=#LINK#]#TITLE#[/url]\n\n#BODY#";
}
$arParams["SUBSCRIBE_AUTHOR_ELEMENT"] = (isset($arParams["SUBSCRIBE_AUTHOR_ELEMENT"]) && $arParams["SUBSCRIBE_AUTHOR_ELEMENT"] == "Y" ? "Y" : "N");
$arParams["IMAGE_SIZE"] = (isset($arParams["IMAGE_SIZE"]) && intVal($arParams["IMAGE_SIZE"]) > 0 ? $arParams["IMAGE_SIZE"] : 300);
$arParams["MESSAGES_PER_PAGE"] = intVal($arParams["MESSAGES_PER_PAGE"] > 0 ? $arParams["MESSAGES_PER_PAGE"] : COption::GetOptionString("forum", "MESSAGES_PER_PAGE", "10"));
$arParams["DATE_TIME_FORMAT"] = trim(empty($arParams["DATE_TIME_FORMAT"]) ? \Bitrix\Tasks\UI::getDateTimeFormat() : $arParams["DATE_TIME_FORMAT"]);
$arParams["USE_CAPTCHA"] = ($arParams["USE_CAPTCHA"] == "Y" ? "Y" : "N");

if ( ! in_array($arParams["PREORDER"], array('Y', 'N', 'ACCORD_FORUM_SETTINGS'), true) )
	$arParams['PREORDER'] = 'N';

$arParams["PAGE_NAVIGATION_TEMPLATE"] = trim($arParams["PAGE_NAVIGATION_TEMPLATE"]);
$arParams["PAGE_NAVIGATION_TEMPLATE"] = (!empty($arParams["PAGE_NAVIGATION_TEMPLATE"]) ? $arParams["PAGE_NAVIGATION_TEMPLATE"] : "modern");
/* * *************** STANDART *************************************** */
if ($arParams["CACHE_TYPE"] == "Y" || ($arParams["CACHE_TYPE"] == "A" && COption::GetOptionString("main", "component_cache_on", "Y") == "Y"))
{
	$arParams["CACHE_TIME"] = intval($arParams["CACHE_TIME"]);
}
else
{
	$arParams["CACHE_TIME"] = 0;
}

if (!isset($arParams['AVATAR_SIZE']))
	$arParams['AVATAR_SIZE'] = array('width' => 30, 'height' => 30);

// activation rating
CRatingsComponentsMain::GetShowRating($arParams);
/* * ******************************************************************
/	Input params
 * ****************************************************************** */

/* * ******************************************************************
	Default values
 * ****************************************************************** */
$cache = new CPHPCache();
$cache_path_main = str_replace(array(":", "//"), "/", "/".SITE_ID."/".$componentName);
$arError = array();
$arNote = array();
$arResult["ERROR_MESSAGE"] = "";
$arResult["OK_MESSAGE"] = (isset($_REQUEST["result"]) && $_REQUEST["result"] == "reply" ? GetMessage("COMM_COMMENT_OK") : (isset($_REQUEST["result"]) && $_REQUEST["result"] == "not_approved" ? GetMessage("COMM_COMMENT_OK_AND_NOT_APPROVED") : ""));
unset($_GET["result"]);
unset($GLOBALS["HTTP_GET_VARS"]["result"]);
DeleteParam(array("result"));

$arResult["MESSAGES"] = array();
$arResult["MESSAGE_VIEW"] = array();
$arResult["MESSAGE"] = array();
$arResult["FILES"] = array();
$arResult["FORUM_TOPIC_ID"] = 0;

// TASK
$arResult['TASK'] = null;

if ($arParams['TASK'])
	$arResult['TASK'] = $arParams['TASK'];
elseif (isset($arParams['TASK_ID']) && ($arParams['TASK_ID'] >= 1))
{
	$rsTask = CTasks::GetById($arParams['TASK_ID']);
	$arTask = $rsTask->Fetch();

	if ($arTask)
		$arResult['TASK'] = $arTask;
}

if ($arResult['TASK'])
{
	$arResult["FORUM_TOPIC_ID"] = (int) $arResult["TASK"]["FORUM_TOPIC_ID"];

	if ($arResult["FORUM_TOPIC_ID"])
	{
		$arTopic = CForumTopic::GetByID($arResult["FORUM_TOPIC_ID"]);
		if ($arTopic)
		{
			$arParams["FORUM_ID"] = $arTopic["FORUM_ID"];
		}
		else
		{
			$arResult["FORUM_TOPIC_ID"] = 0;
		}
	}
}

$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

$arTaskUsers = CTasks::__GetSearchPermissions($arResult["TASK"]);

/* * ******************************************************************
	External permissions from tasks
 * ****************************************************************** */
// A - NO ACCESS		E - READ			I - ANSWER
// M - NEW TOPIC		Q - MODERATE	U - EDIT			Y - FULL_ACCESS

if (
	($USER->CanAccess($arTaskUsers) === true)
	|| $USER->IsAdmin()
	|| CTasksTools::IsPortalB24Admin()
)
{
	$arParams['PERMISSION'] = 'M';
}
else
	$arParams['PERMISSION'] = 'A';

if (!CForumNew::CanUserViewForum($arParams["FORUM_ID"], $USER->GetUserGroupArray(), $arParams["PERMISSION"]))
{
	if ( ! ($USER->IsAdmin() || CTasksTools::IsPortalB24Admin()) )
	{
		ShowError(GetMessage("F_ERR_FORUM_NO_ACCESS"));
		return false;
	}
}
/* * ******************************************************************
/	External permissions from tasks
 * ****************************************************************** */

$arResult["FORUM"] = CForumNew::GetByID($arParams["FORUM_ID"]);
$arResult["ELEMENT"] = array();
$arResult["USER"] = array(
	"PERMISSION" => $arParams['PERMISSION'],
	"SHOWED_NAME" => $GLOBALS["FORUM_STATUS_NAME"]["guest"],
	"SUBSCRIBE" => array(),
	"FORUM_SUBSCRIBE" => "N", "TOPIC_SUBSCRIBE" => "N"
);

if ($USER->IsAuthorized())
{
	$tmpName = CUser::FormatName($arParams["NAME_TEMPLATE"],array(	"NAME"		 	=> $GLOBALS["USER"]->GetFirstName(), 
																	"LAST_NAME" 	=> $GLOBALS["USER"]->GetLastName(), 
																	"SECOND_NAME" 	=> $GLOBALS["USER"]->GetSecondName(), 
																	"LOGIN" 		=> $GLOBALS["USER"]->GetLogin()));

	$arResult["USER"]["SHOWED_NAME"] = trim($_SESSION["FORUM"]["SHOW_NAME"] == "Y" ? $tmpName : $GLOBALS["USER"]->GetLogin());
	$arResult["USER"]["SHOWED_NAME"] = trim(!empty($arResult["USER"]["SHOWED_NAME"]) ? $arResult["USER"]["SHOWED_NAME"] : $GLOBALS["USER"]->GetLogin());
}

$arResult["SHOW_PANEL_ATTACH_IMG"] = (in_array($arResult["FORUM"]["ALLOW_UPLOAD"], array("A", "F", "Y")) ? "Y" : "N");
$arResult["TRANSLIT"] = (LANGUAGE_ID == "ru" ? "Y" : " N");
if ($arResult["FORUM"]["ALLOW_SMILES"] == "Y")
{
	$arResult["ForumPrintSmilesList"] = ($arResult["FORUM"]["ALLOW_SMILES"] == "Y" ? ForumPrintSmilesList(3, LANGUAGE_ID, $arParams["PATH_TO_SMILE"], $arParams["CACHE_TIME"]) : "");
	$arResult["SMILES"] = CForumSmile::GetByType("S", LANGUAGE_ID);
	foreach($arResult["SMILES"] as $key=>$smile)
	{
		$arResult["SMILES"][$key]["IMAGE"] = $arParams["PATH_TO_SMILE"].$smile["IMAGE"];
		$arResult["SMILES"][$key]["DESCRIPTION"] = $arResult["SMILES"][$key]["NAME"];
		list($arResult["SMILES"][$key]["TYPING"],) = explode(" ", $smile["TYPING"]);
	}
}

// PARSER
$parser = new CTextParser();
$parser->imageWidth = $arParams["IMAGE_SIZE"];
$parser->imageHeight = $arParams["IMAGE_SIZE"];
$parser->smiles = $arResult["SMILES"];
$parser->allow = array(
	"HTML" => $arResult["FORUM"]["ALLOW_HTML"],
	"ANCHOR" => $arResult["FORUM"]["ALLOW_ANCHOR"],
	"BIU" => $arResult["FORUM"]["ALLOW_BIU"],
	"IMG" => "Y",
	"VIDEO" => "N",
	"LIST" => $arResult["FORUM"]["ALLOW_LIST"],
	"QUOTE" => $arResult["FORUM"]["ALLOW_QUOTE"],
	"CODE" => $arResult["FORUM"]["ALLOW_CODE"],
	"FONT" => $arResult["FORUM"]["ALLOW_FONT"],
	"SMILES" => $arResult["FORUM"]["ALLOW_SMILES"],
	"UPLOAD" => $arResult["FORUM"]["ALLOW_UPLOAD"],
	"NL2BR" => $arResult["FORUM"]["ALLOW_NL2BR"],
	"TABLE" => "Y"
);
$_REQUEST["FILES"] = isset($_REQUEST["FILES"]) && is_array($_REQUEST["FILES"]) ? $_REQUEST["FILES"] : array();
$_REQUEST["FILES_TO_UPLOAD"] = isset($_REQUEST["FILES_TO_UPLOAD"]) && is_array($_REQUEST["FILES_TO_UPLOAD"]) ? $_REQUEST["FILES_TO_UPLOAD"] : array();
CPageOption::SetOptionString("main", "nav_page_in_session", "N");
/* * ******************************************************************
/	Default values
 * ****************************************************************** */

if (empty($arResult["FORUM"]))
{
	ShowError(str_replace("#FORUM_ID#", $arParams["FORUM_ID"], GetMessage("F_ERR_FID_IS_NOT_EXIST")));
	return false;
}
elseif (empty($arResult["TASK"]))
{
	ShowError(str_replace("#TASK_ID#", $arParams["TASK_ID"], GetMessage("F_ERR_TID_IS_NOT_EXIST")));
	return false;
}

/* * ******************************************************************
	Actions
 * ****************************************************************** */
ForumSetLastVisit($arParams["FORUM_ID"], 0);
if (
	isset($_POST["REVIEW_TEXT"])
	|| (isset($_POST['remove_comment']) && ($_POST['remove_comment'] === 'Y'))
)
{
	$path = str_replace(array("\\", "//"), "/", dirname(__FILE__)."/action.php");
	include($path);
	$strErrorMessage = "";
	foreach ($arError as $res)
	{
		$strErrorMessage .= ( empty($res["title"]) ? $res["code"] : $res["title"]);
	}

	$arResult["ERROR_MESSAGE"] = $strErrorMessage;
	$arResult["OK_MESSAGE"] .= $strOKMessage;
}
/* * ******************************************************************
/	Actions
 * ****************************************************************** */

/* * ******************************************************************
	Input params II
 * ****************************************************************** */
/* * ************ URL *********************************************** */
if (empty($arParams["~URL_TEMPLATES_READ"]) && !empty($arResult["FORUM"]["PATH2FORUM_MESSAGE"]))
{
	$arParams["~URL_TEMPLATES_READ"] = $arResult["FORUM"]["PATH2FORUM_MESSAGE"];
}
elseif (empty($arParams["~URL_TEMPLATES_READ"]))
{
	$arParams["~URL_TEMPLATES_READ"] = $APPLICATION->GetCurPage()."?PAGE_NAME=read&FID=#FID#&TID=#TID#&MID=#MID#";
}
$arParams["~URL_TEMPLATES_READ"] = str_replace(array("#FORUM_ID#", "#TOPIC_ID#", "#MESSAGE_ID#"), array("#FID#", "#TID#", "#MID#"), $arParams["~URL_TEMPLATES_READ"]);
$arParams["URL_TEMPLATES_READ"] = htmlspecialcharsEx($arParams["~URL_TEMPLATES_READ"]);
/* * ************ ADDITIONAL **************************************** */
$arParams["USE_CAPTCHA"] = $arResult["FORUM"]["USE_CAPTCHA"] == "Y" ? "Y" : $arParams["USE_CAPTCHA"];
/* * ******************************************************************
/	Input params
 * ****************************************************************** */

/** * *****************************************************************
	Data
 * ****************************************************************** */
/* * ************ 3. Get inormation about USER ********************** */
if ($GLOBALS["USER"]->IsAuthorized() && $arResult["USER"]["PERMISSION"] > "E")
{
	// USER subscribes
	$arUserSubscribe = array();
	$arFields = array("USER_ID" => $GLOBALS["USER"]->GetID(), "FORUM_ID" => $arParams["FORUM_ID"]);
	$db_res = CForumSubscribe::GetList(array(), $arFields);
	if ($db_res && $res = $db_res->Fetch())
	{
		do
		{
			$arUserSubscribe[] = $res;
		}
		while ($res = $db_res->Fetch());
	}
	$arResult["USER"]["SUBSCRIBE"] = $arUserSubscribe;
	foreach ($arUserSubscribe as $res)
	{
		if (intVal($res["TOPIC_ID"]) <= 0)
		{
			$arResult["USER"]["FORUM_SUBSCRIBE"] = "Y";
		}
		elseif (intVal($res["TOPIC_ID"]) == intVal($arResult["FORUM_TOPIC_ID"]))
		{
			$arResult["USER"]["TOPIC_SUBSCRIBE"] = "Y";
		}
	}
}

/* * ************ 4. Get message list ******************************* */
$ORDER_DIRECTION = 'ASC';
if ($arParams['PREORDER'] === 'ACCORD_FORUM_SETTINGS')
	$ORDER_DIRECTION = strtoupper($arResult['FORUM']['ORDER_DIRECTION']);
elseif ($arParams['PREORDER'] === 'N')
	$ORDER_DIRECTION = 'DESC';

if ( ! in_array($ORDER_DIRECTION, array('ASC', 'DESC'), true) )
	$ORDER_DIRECTION = 'ASC';

$arResult['ORDER_DIRECTION'] = $ORDER_DIRECTION;

$pageNo = 0;
if ($arResult["FORUM_TOPIC_ID"] > 0)
{
	$page_number = $GLOBALS["NavNum"] + 1;

	$MID = intVal($_REQUEST["MID"]);
	unset($_GET["MID"]);
	unset($GLOBALS["MID"]);
	if (intVal($MID) > 0)
	{
		$pageNo = CForumMessage::GetMessagePage(
			$MID, $arParams["MESSAGES_PER_PAGE"], $GLOBALS["USER"]->GetUserGroupArray(), $arResult["FORUM_TOPIC_ID"], array(
				"ORDER_DIRECTION" => $ORDER_DIRECTION,
				"PERMISSION_EXTERNAL" => $arResult["USER"]["PERMISSION"],
				"FILTER" => array("!PARAM1" => "IB")
			)
		);
	}
	else
	{
		$pageNo = $_GET["PAGEN_".$page_number];
		if (isset($arResult['RESULT']) && intval($arResult['RESULT']) > 0) $pageNo = $arResult['RESULT'];
	}
	if ($pageNo > 200) $pageNo = 0;

	$arMessages = array();
	$ar_cache_id = array(
		$arParams["FORUM_ID"], 
		$arParams["TASK_ID"], 
		$arResult["FORUM_TOPIC_ID"], 
		$arParams["MESSAGES_PER_PAGE"], 
		$arParams["DATE_TIME_FORMAT"], 
		$ORDER_DIRECTION,
		$pageNo, 
		$arResult['FORUM']['LAST_POST_DATE'] . $arResult['FORUM']['ABS_LAST_POST_DATE'],
		$arResult['TASK']['COMMENTS_COUNT']
	);
	if ($_GET["IFRAME"] == "Y")
	{
		$ar_cache_id[] = "IFRAME";
	}
	$cache_id = "forum_message_".sha1(serialize($ar_cache_id));
	if(($tzOffset = CTimeZone::GetOffset()) <> 0)
		$cache_id .= "_".$tzOffset;

	$cache_path = $cache_path_main;
	if ($arParams["CACHE_TIME"] > 0 && $cache->InitCache($arParams["CACHE_TIME"], $cache_id, $cache_path))
	{
		$res = $cache->GetVars();
		if (is_array($res["arMessages"]))
		{
			$arMessages = $res["arMessages"];

			$arResult["NAV_PAGE_COUNT"] = false;

			if ($db_res)
				$arResult['NAV_PAGE_COUNT'] = $db_res->NavPageCount;

			if (is_array($res["Nav"]))
			{
				$arResult["NAV_PAGE_COUNT"] = $res["Nav"]["NAV_PAGE_COUNT"];
				$arResult["NAV_STRING"] = $res["Nav"]["NAV_STRING"];
			}
		}
	}

	if (empty($arMessages))
	{
		$arOrder = array("ID" => $ORDER_DIRECTION);
		$arFields = array(
			"bDescPageNumbering" => false,
			"nPageSize" => $arParams["MESSAGES_PER_PAGE"],
			'iNumPage' => $arFields["iNumPage"] > 0 ? $arFields["iNumPage"] : false,
			"bShowAll" => false
		);

		if ((intVal($MID) > 0) && ($pageNo > 0))
				$arFields["iNumPage"] = intVal($pageNo);

		$db_res = CForumMessage::GetListEx(
			$arOrder,
			array(
				"FORUM_ID" => $arParams["FORUM_ID"],
				"TOPIC_ID" => $arResult["FORUM_TOPIC_ID"],
				"APPROVED" => "Y"
			),
			false,
			0,
			$arFields
		);

		$arResult["NAV_PAGE_COUNT"] = false;

		if ($db_res)
			$arResult['NAV_PAGE_COUNT'] = $db_res->NavPageCount;

		$arAvatars = array();
		if ($db_res)
		{
			$arResult["NAV_STRING"] = $db_res->GetPageNavStringEx($navComponentObject, GetMessage("NAV_OPINIONS"), $arParams["PAGE_NAVIGATION_TEMPLATE"]);
			$number = intVal($db_res->NavPageNomer - 1) * $arParams["MESSAGES_PER_PAGE"] + 1;
			while ($res = $db_res->GetNext())
			{
				/*				 * ************ Message info ************************************** */
				// number in topic
				$res["NUMBER"] = $number++;
				// data
				$res["~POST_DATE"] = $res["POST_DATE"];
				$res["~EDIT_DATE"] = $res["EDIT_DATE"];
				$res["POST_DATE"] = CForumFormat::DateFormat($arParams["DATE_TIME_FORMAT"], MakeTimeStamp($res["POST_DATE"], CSite::GetDateFormat()));
				$res["EDIT_DATE"] = CForumFormat::DateFormat($arParams["DATE_TIME_FORMAT"], MakeTimeStamp($res["EDIT_DATE"], CSite::GetDateFormat()));
				// text
				$res["~POST_MESSAGE_TEXT"] = (COption::GetOptionString("forum", "FILTER", "Y") == "Y" ? $res["~POST_MESSAGE_FILTER"] : $res["~POST_MESSAGE"]);
				$res["POST_MESSAGE_TEXT"] = $parser->convertText($res["~POST_MESSAGE_TEXT"]);
				// attach
				$res["ATTACH_IMG"] = "";
				$res["FILES"] = array();
				$res["~ATTACH_FILE"] = array();
				$res["ATTACH_FILE"] = array();
				/*				 * ************ Message info/************************************** */
				/*				 * ************ Author info *************************************** */
				$res["AUTHOR_ID"] = intVal($res["AUTHOR_ID"]);
				$res["AUTHOR_URL"] = "";
				if (!empty($arParams["URL_TEMPLATES_PROFILE_VIEW"]))
				{
					$res["AUTHOR_URL"] = CComponentEngine::MakePathFromTemplate($arParams["URL_TEMPLATES_PROFILE_VIEW"], array("user_id" => $res["AUTHOR_ID"]));
				}
				if (!isset($arAvatars[$res["AUTHOR_ID"]]))
				{
					$arAvatars[$res["AUTHOR_ID"]] = false;
					$rsUser = CUser::GetByID($res["AUTHOR_ID"]);
					if ($user = $rsUser->Fetch())
					{
						if (intval($user["PERSONAL_PHOTO"]) > 0)
						{
							$imageFile = CFile::GetFileArray($user["PERSONAL_PHOTO"]);
							if ($imageFile !== false)
							{
								$arFileTmp = CFile::ResizeImageGet(
									$imageFile, 
									array(
										"width"  => $arParams['AVATAR_SIZE']['width'], 
										"height" => $arParams['AVATAR_SIZE']['height']
									), 
									BX_RESIZE_IMAGE_EXACT, 
									false
								);
								$arAvatars[$res["AUTHOR_ID"]] = $arFileTmp["src"];
							}
						}
					}
				}
				$res["AUTHOR_PHOTO"] = $arAvatars[$res["AUTHOR_ID"]];
				/************** Author info/*************************************** */
				// For quote JS
				$res["FOR_JS"]["AUTHOR_NAME"] = Cutil::JSEscape($res["AUTHOR_NAME"]);
				$res["FOR_JS"]["POST_MESSAGE_TEXT"] = Cutil::JSEscape(htmlspecialcharsbx($res["POST_MESSAGE_TEXT"]));
				$res["FOR_JS"]["POST_MESSAGE"] = Cutil::JSEscape(htmlspecialcharsbx($res["~POST_MESSAGE"]));

				// Forum store name of author permamently
				// When name of author changes => in comments we see old name
				// So, we just get dynamically name on every request (except cache)
				$res['AUTHOR_DYNAMIC_NAME_AS_ARRAY'] = false;
				$rc = CUser::GetByID($res['AUTHOR_ID']);
				if ($arDynName = $rc->Fetch())
				{
					$res['AUTHOR_DYNAMIC_NAME_AS_ARRAY'] = array(
						'LOGIN'       => $arDynName['LOGIN'],
						'NAME'        => $arDynName['NAME'],
						'SECOND_NAME' => $arDynName['SECOND_NAME'],
						'LAST_NAME'   => $arDynName['LAST_NAME']
						);
				}

				$res["FOR_JS"]["AUTHOR_DYNAMIC_NAME"] = Cutil::JSEscape(
					tasksFormatName(
						$res['AUTHOR_DYNAMIC_NAME_AS_ARRAY']['NAME'], 
						$res['AUTHOR_DYNAMIC_NAME_AS_ARRAY']['LAST_NAME'], 
						$res['AUTHOR_DYNAMIC_NAME_AS_ARRAY']['LOGIN'], 
						$res['AUTHOR_DYNAMIC_NAME_AS_ARRAY']['SECOND_NAME'], 
						$arParams['NAME_TEMPLATE'],
						true	// escape special chars
						)
					);

				$arMessages[$res["ID"]] = $res;
			}
		}
		/*		 * ************ Attach files ************************************** */
		if (!empty($arMessages))
		{
			$res = array_keys($arMessages);
			$arFilter = array("FORUM_ID" => $arParams["FORUM_ID"], "TOPIC_ID" => $arResult["FORUM_TOPIC_ID"], "APPROVED" => "Y", ">MESSAGE_ID" => intVal(min($res)) - 1, "<MESSAGE_ID" => intVal(max($res)) + 1);
			$db_files = CForumFiles::GetList(array("MESSAGE_ID" => "ASC"), $arFilter);
			if ($db_files && $res = $db_files->Fetch())
			{
				do
				{
					$res["SRC"] = CFile::GetFileSRC($res);
					if ($arMessages[$res["MESSAGE_ID"]]["~ATTACH_IMG"] == $res["FILE_ID"])
					{
						// attach for custom
						$arMessages[$res["MESSAGE_ID"]]["~ATTACH_FILE"] = $res;
						$arMessages[$res["MESSAGE_ID"]]["ATTACH_IMG"] = CFile::ShowFile($res["FILE_ID"], 0, $arParams["IMAGE_SIZE"], $arParams["IMAGE_SIZE"], true, "border=0", false);
						$arMessages[$res["MESSAGE_ID"]]["ATTACH_FILE"] = $arMessages[$res["MESSAGE_ID"]]["ATTACH_IMG"];
					}
					$arMessages[$res["MESSAGE_ID"]]["FILES"][$res["FILE_ID"]] = $res;
					$arResult["FILES"][$res["FILE_ID"]] = $res;
				}
				while ($res = $db_files->Fetch());
			}
		}
		/*		 * ************ Message List/************************************** */
		if ($arParams["CACHE_TIME"] > 0)
		{
			$cache->StartDataCache($arParams["CACHE_TIME"], $cache_id, $cache_path);
			$cache->EndDataCache(array(
				"arMessages" => $arMessages,
				"Nav" => array(
					"NAV_PAGE_COUNT" => $arResult["NAV_PAGE_COUNT"],
					"NAV_STRING" => $arResult["NAV_STRING"]
				)
			));
		}
	}
	else
	{
		$GLOBALS["NavNum"]++;
	}
	/************** Rating ****************************************/

	if ($arParams["SHOW_RATING"] == "Y") {
		$arMessageIDs = array_keys($arMessages);
		$arRatings = CRatings::GetRatingVoteResult('FORUM_POST', $arMessageIDs);
		if ($arRatings)
		foreach($arRatings as $postID => $arRating)
			$arMessages[$postID]['RATING'] = $arRating;
	}
	$arResult["MESSAGES"] = $arMessages;
	// Link to forum
	$arResult["read"] = CComponentEngine::MakePathFromTemplate($arParams["URL_TEMPLATES_READ"], array("FID" => $arParams["FORUM_ID"], "TID" => $arResult["FORUM_TOPIC_ID"], "MID" => "s", "PARAM1" => "IB", "PARAM2" => $arParams["ELEMENT_ID"]));
}


/* * ************ 5. Show post form ********************************* */
$arResult["SHOW_POST_FORM"] = "Y";

if ($arResult["SHOW_POST_FORM"] == "Y")
{
	// Author name
	$arResult["~REVIEW_AUTHOR"] = $arResult["USER"]["SHOWED_NAME"];
	$arResult["~REVIEW_USE_SMILES"] = ($arResult["FORUM"]["ALLOW_SMILES"] == "Y" ? "Y" : "N");

	if (!empty($arError) || !empty($arResult["MESSAGE_VIEW"]))
	{
		if (!empty($_POST["REVIEW_AUTHOR"]))
		{
			$arResult["~REVIEW_AUTHOR"] = $_POST["REVIEW_AUTHOR"];
		}
		$arResult["~REVIEW_EMAIL"] = $_POST["REVIEW_EMAIL"];
		$arResult["~REVIEW_TEXT"] = $_POST["REVIEW_TEXT"];
		$arResult["~REVIEW_USE_SMILES"] = ($_POST["REVIEW_USE_SMILES"] == "Y" ? "Y" : "N");
	}
	$arResult["REVIEW_AUTHOR"] = isset($arResult["~REVIEW_AUTHOR"]) ? htmlspecialcharsEx($arResult["~REVIEW_AUTHOR"]) : "";
	$arResult["REVIEW_EMAIL"] = isset($arResult["~REVIEW_EMAIL"]) ? htmlspecialcharsEx($arResult["~REVIEW_EMAIL"]) : "";
	$arResult["REVIEW_TEXT"] = isset($arResult["~REVIEW_TEXT"]) ? htmlspecialcharsEx($arResult["~REVIEW_TEXT"]) : "";
	$arResult["REVIEW_USE_SMILES"] = $arResult["~REVIEW_USE_SMILES"];
	$arResult["REVIEW_FILES"] = array();
	foreach ($_REQUEST["FILES"] as $key => $val)
	{
		if (intVal($val) <= 0)
			continue;

		$resForumFile = CForumFiles::GetList(array(), array('FILE_ID' => $val));
		if ($resForumFile && ($arForumFile = $resForumFile->Fetch()))
		{
			$bFileAccessible = false;

			// Workaround for just uploaded files
			if (($arForumFile['MESSAGE_ID'] == 0) && ($arForumFile['TOPIC_ID'] == 0))
				$bFileAccessible = true;
			else
			{
				$arTmp['MESSAGE'] = CForumMessage::GetByIDEx(
					$arForumFile['MESSAGE_ID'],
					array('GET_FORUM_INFO' => 'N', 'GET_TOPIC_INFO' => 'Y')
				);
				if (
					isset($arTmp['MESSAGE']['TOPIC_INFO']['ID'])
					&& CTasks::CanCurrentUserViewTopic($arTmp['MESSAGE']['TOPIC_INFO']['ID'])
				)
				{
					$bFileAccessible = true;
				}
			}

			if ($bFileAccessible)
				$arResult['REVIEW_FILES'][$val] = CFile::GetFileArray($val);
		}
	}
}

$arResult["SHOW_CLOSE_ALL"] = "N";
if (
		$arResult["FORUM"]["ALLOW_BIU"] == "Y" ||
		$arResult["FORUM"]["ALLOW_FONT"] == "Y" ||
		$arResult["FORUM"]["ALLOW_ANCHOR"] == "Y" ||
		$arResult["FORUM"]["ALLOW_IMG"] == "Y" ||
		$arResult["FORUM"]["ALLOW_QUOTE"] == "Y" ||
		$arResult["FORUM"]["ALLOW_CODE"] == "Y" ||
		$arResult["FORUM"]["ALLOW_LIST"] == "Y"
)
{
	$arResult["SHOW_CLOSE_ALL"] = "Y";
}

/* For custom template */
$arResult["LANGUAGE_ID"] = LANGUAGE_ID;
$arResult["IS_AUTHORIZED"] = $GLOBALS["USER"]->IsAuthorized();
$arResult["PERMISSION"] = $arResult["USER"]["PERMISSION"];
$arResult["SHOW_NAME"] = $arResult["USER"]["SHOWED_NAME"];
$arResult["sessid"] = bitrix_sessid_post();
$arResult["SHOW_SUBSCRIBE"] = (isset($arResult["USER"]["ID"]) && $arResult["USER"]["ID"] > 0 && $arResult["USER"]["PERMISSION"] > "E" ? "Y" : "N");
$arResult["TOPIC_SUBSCRIBE"] = $arResult["USER"]["TOPIC_SUBSCRIBE"];
$arResult["FORUM_SUBSCRIBE"] = $arResult["USER"]["FORUM_SUBSCRIBE"];
$arResult["SHOW_LINK"] = (empty($arResult["read"]) ? "N" : "Y");
$arResult["SHOW_POSTS"] = (empty($arResult["MESSAGES"]) ? "N" : "Y");
$arResult["PARSER"] = $parser;
$arResult["CURRENT_PAGE"] = $APPLICATION->GetCurPageParam();
/* For custom template */

// *****************************************************************************************
$this->IncludeComponentTemplate();
// *****************************************************************************************
?>
