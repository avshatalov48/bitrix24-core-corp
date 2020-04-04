<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

use Bitrix\Main\ModuleManager;

if (
	$arResult["SHOW_FULL_FORM"]
	&& $arParams["B_CALENDAR"]
	&& empty($arResult["Post"])
	&& !isset($arParams["DISPLAY"])
	&& !$arResult["bExtranetUser"]
)
{
	$arResult["PostToShow"]["FEED_DESTINATION_CALENDAR"] = $arResult["PostToShow"]["FEED_DESTINATION"];

	$arResult["DEST_SORT_CALENDAR"] = CSocNetLogDestination::GetDestinationSort(array(
		"DEST_CONTEXT" => "CALENDAR",
		"ALLOW_EMAIL_INVITATION" => false
	));
	$arResult["PostToShow"]["FEED_DESTINATION_CALENDAR"]['LAST'] = array();
	CSocNetLogDestination::fillLastDestination($arResult["DEST_SORT_CALENDAR"], $arResult["PostToShow"]["FEED_DESTINATION_CALENDAR"]['LAST']);

	$arDestUser = array();

	if(!empty($arResult["PostToShow"]["FEED_DESTINATION_CALENDAR"]['LAST']['USERS']))
	{
		foreach ($arResult["PostToShow"]["FEED_DESTINATION_CALENDAR"]['LAST']['USERS'] as $value)
		{
			$arDestUser[] = str_replace('U', '', $value);
		}
	}

	$arResult["PostToShow"]["FEED_DESTINATION_CALENDAR"]['USERS'] = CSocNetLogDestination::GetUsers(Array('id' => $arDestUser));
}

if (
	$arResult["SHOW_FULL_FORM"]
	&& $arResult["BLOG_POST_TASKS"]
)
{
	$userPage = \Bitrix\Main\Config\Option::get('socialnetwork', 'user_page', SITE_DIR.'company/personal/');
	$workgroupPage = \Bitrix\Main\Config\Option::get('socialnetwork', 'workgroups_page', SITE_DIR.'workgroups/');

	$arParams['PATH_TO_USER_PROFILE'] = (!empty($arParams['PATH_TO_USER_PROFILE']) ? $arParams['PATH_TO_USER_PROFILE'] : $workgroupPage.'user/#user_id#/');
	$arParams['PATH_TO_GROUP'] = (!empty($arParams['PATH_TO_GROUP']) ? $arParams['PATH_TO_GROUP'] : $workgroupPage.'group/#group_id#/');
	$arParams['PATH_TO_USER_TASKS'] = (!empty($arParams['PATH_TO_USER_TASKS']) ? $arParams['PATH_TO_USER_TASKS'] : $userPage.'user/#user_id#/tasks/');
	$arParams['PATH_TO_USER_TASKS_TASK'] = (!empty($arParams['PATH_TO_USER_TASKS_TASK']) ? $arParams['PATH_TO_USER_TASKS_TASK'] : $userPage.'user/#user_id#/tasks/task/#action#/#task_id#/');
	$arParams['PATH_TO_GROUP_TASKS'] = (!empty($arParams['PATH_TO_GROUP_TASKS']) ? $arParams['PATH_TO_GROUP_TASKS'] : $workgroupPage.'group/#group_id#/tasks/');
	$arParams['PATH_TO_GROUP_TASKS_TASK'] = (!empty($arParams['PATH_TO_GROUP_TASKS_TASK']) ? $arParams['PATH_TO_GROUP_TASKS_TASK'] : $workgroupPage.'group/#group_id#/tasks/task/#action#/#task_id#/');
	$arParams['PATH_TO_USER_TASKS_PROJECTS_OVERVIEW'] = (!empty($arParams['PATH_TO_USER_TASKS_PROJECTS_OVERVIEW']) ? $arParams['PATH_TO_USER_TASKS_PROJECTS_OVERVIEW'] : $userPage.'user/#user_id#/tasks/projects/');
	$arParams['PATH_TO_USER_TASKS_TEMPLATES'] = (!empty($arParams['PATH_TO_USER_TASKS_TEMPLATES']) ? $arParams['PATH_TO_USER_TASKS_TEMPLATES'] : $userPage.'user/#user_id#/tasks/templates/');
	$arParams['PATH_TO_USER_TEMPLATES_TEMPLATE'] = (!empty($arParams['PATH_TO_USER_TEMPLATES_TEMPLATE']) ? $arParams['PATH_TO_USER_TEMPLATES_TEMPLATE'] : $userPage.'user/#user_id#/tasks/templates/template/#action#/#template_id#/');
	$arParams['TASK_SUBMIT_BACKURL'] = $APPLICATION->GetCurPageParam(isset($arParams["LOG_EXPERT_MODE"]) && $arParams["LOG_EXPERT_MODE"] == 'Y' ? "taskIdCreated=#task_id#" : "", array(
		"flt_created_by_id",
		"flt_group_id",
		"flt_to_user_id",
		"flt_date_datesel",
		"flt_date_days",
		"flt_date_from",
		"flt_date_to",
		"flt_date_to",
		"preset_filter_id",
		"sessid",
		"bxajaxid",
		"logajax"
	));
}

if (
	isset($_GET["taskIdCreated"])
	&& intval($_GET["taskIdCreated"]) > 0
)
{
	$_SESSION["SL_TASK_ID_CREATED"] = intval($_GET["taskIdCreated"]);
	LocalRedirect($APPLICATION->GetCurPageParam("", array("taskIdCreated", "EVENT_TYPE", "EVENT_TASK_ID", "EVENT_OPTION")));
}

$arResult["SHOW_BLOG_FORM_TARGET"] = isset($arParams["SHOW_BLOG_FORM_TARGET"]) && $arParams["SHOW_BLOG_FORM_TARGET"];
if (isset($arResult["POST_PROPERTIES"]["DATA"])
	&& isset($arResult["POST_PROPERTIES"]["DATA"]["UF_IMPRTANT_DATE_END"])
	&& isset($arResult["POST_PROPERTIES"]["DATA"]["UF_IMPRTANT_DATE_END"]["VALUE"])
	&& $arResult["POST_PROPERTIES"]["DATA"]["UF_IMPRTANT_DATE_END"]["VALUE"])
{
	$postImportantTillDate = new \Bitrix\Main\Type\DateTime($arResult["POST_PROPERTIES"]["DATA"]["UF_IMPRTANT_DATE_END"]["VALUE"]);
	$postImportantTillDate = $postImportantTillDate->add("1D");
	$arResult["POST_PROPERTIES"]["DATA"]["UF_IMPRTANT_DATE_END"]["VALUE"] = $postImportantTillDate->format(\Bitrix\Main\Type\Date::convertFormatToPhp(\CSite::GetDateFormat('SHORT')));
}

if (is_array($arResult["REMAIN_IMPORTANT_TILL"]))
{
	$arResult["REMAIN_IMPORTANT_DEFAULT_OPTION"] = reset($arResult["REMAIN_IMPORTANT_TILL"]);
	foreach ($arResult["REMAIN_IMPORTANT_TILL"] as $key => $attributesForPopupList)
	{
		if ($attributesForPopupList["VALUE"] === "CUSTOM")
		{
			$arResult["REMAIN_IMPORTANT_TILL"][$key]["CLASS"] = "js-custom-date-end";
		}
		else
		{
			$arResult["REMAIN_IMPORTANT_TILL"][$key]["CLASS"] = "";
			if ($attributesForPopupList["VALUE"] === "WEEK")
			{
				$arResult["REMAIN_IMPORTANT_DEFAULT_OPTION"]['TEXT_KEY'] = $arResult["REMAIN_IMPORTANT_TILL"][$key]["TEXT_KEY"];
				$arResult["REMAIN_IMPORTANT_DEFAULT_OPTION"]['VALUE'] = $arResult["REMAIN_IMPORTANT_TILL"][$key]["VALUE"];
			}
		}
	}
}

$arResult['bVarsFromForm'] = (array_key_exists("POST_MESSAGE", $_REQUEST) || strlen($arResult["ERROR_MESSAGE"]) > 0 || $arResult["needShow"]);
$arResult['tabActive'] = ($arResult['bVarsFromForm'] ? $_REQUEST["changePostFormTab"] : "message");

$arResult['tabs'] = array();

if (
	ModuleManager::isModuleInstalled("intranet")
	&& (
		(
			is_array($arResult["PostToShow"]["GRATS"])
			&& !empty($arResult["PostToShow"]["GRATS"])
			&& (!isset($arParams["PAGE_ID"]) || $arParams["PAGE_ID"] != "user_blog_post_edit_profile")
		)
		|| (
			isset($arParams["PAGE_ID"])
			&& $arParams["PAGE_ID"] == "user_blog_post_edit_grat"
		)
	)
)
{
	$arResult['tabs'][] = 'grat';

	if (
		!empty($arResult["PostToShow"]["GRAT_CURRENT"]["ID"])
		|| !empty($arResult["PostToShow"]["GRAT_CURRENT"]["USERS"])
		|| (
			isset($arParams["PAGE_ID"])
			&& in_array($arParams["PAGE_ID"], [ 'user_blog_post_edit_grat', 'user_grat' ])
		)
	)
	{
		$arResult['tabActive'] = "grat";
	}

	if (
		array_key_exists("GRAT_CURRENT", $arResult["PostToShow"])
		&& is_array($arResult["PostToShow"]["GRAT_CURRENT"]["USERS"])
	)
	{
		$arResult['arGratCurrentUsers'] = array();
		foreach($arResult["PostToShow"]["GRAT_CURRENT"]["USERS"] as $grat_user_id)
		{
			$arResult['arGratCurrentUsers']["U".$grat_user_id] = 'users';
		}
	}
	elseif (
		isset($arParams["PAGE_ID"])
		&& in_array($arParams["PAGE_ID"], [ 'user_blog_post_edit_grat', 'user_grat' ])
	)
	{
		$arResult['arGratCurrentUsers']["U".(!empty($_REQUEST['gratUserId']) ? intval($_REQUEST['gratUserId']) : $arParams['USER_ID'])] = 'users';
	}
}

if ($arResult["BLOG_POST_TASKS"])
{
	$arResult['tabs'][] = 'tasks';
}

if (
	$arParams["B_CALENDAR"]
	&& empty($arResult["Post"])
	&& !isset($arParams["DISPLAY"])
	&& !$arResult["bExtranetUser"]
)
{
	$arResult['tabs'][] = 'calendar';
}

if (
	$arResult["BLOG_POST_LISTS"]
	&& empty($arResult["Post"])
	&& !isset($arParams["DISPLAY"])
	&& !$arResult["bExtranetUser"]
)
{
	$arResult['tabs'][] = 'lists';
}

if (
	empty($arResult["Post"])
	&& array_key_exists("UF_BLOG_POST_FILE", $arResult["POST_PROPERTIES"]["DATA"])
)
{
	$arResult['tabs'][] = 'file';
}

if (
	array_key_exists("UF_BLOG_POST_VOTE", $arResult["POST_PROPERTIES"]["DATA"])
	&& (
		!isset($arParams["PAGE_ID"])
		|| !in_array($arParams["PAGE_ID"], array("user_blog_post_edit_profile", "user_blog_post_edit_grat"))
	)
)
{
	$arResult['tabs'][] = 'vote';

	if (
		!$arResult['bVarsFromForm']
		&& !!$arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_VOTE"]["VALUE"]
	)
	{
		$arResult['tabActive'] = "vote";
	}
}

if (
	!$arResult['bVarsFromForm']
	&& array_key_exists("UF_BLOG_POST_IMPRTNT", $arResult["POST_PROPERTIES"]["DATA"])
	&& !!$arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_IMPRTNT"]["VALUE"]

)
{
	$arResult['tabActive'] = "important";
}