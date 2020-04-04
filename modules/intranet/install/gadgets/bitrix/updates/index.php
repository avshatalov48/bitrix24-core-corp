<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if(!CModule::IncludeModule("socialnetwork"))
	return false;

$arGadgetParams["TEMPLATE_NAME"] = ($arGadgetParams["TEMPLATE_NAME"]?$arGadgetParams["TEMPLATE_NAME"]:"main");
$arGadgetParams["SHOW_TITLE"] = ($arGadgetParams["SHOW_TITLE"]?$arGadgetParams["SHOW_TITLE"]:"N");
$arGadgetParams["GROUP_ID"] = ($arGadgetParams["GROUP_ID"]?$arGadgetParams["GROUP_ID"]:false);

$arGadgetParams["ENTITY_TYPE"] = ($arGadgetParams["ENTITY_TYPE"] == SONET_ENTITY_USER ? SONET_ENTITY_USER : ($arGadgetParams["ENTITY_TYPE"] == SONET_ENTITY_GROUP ? SONET_ENTITY_GROUP : ""));



if (!is_array($arGadgetParams["EVENT_ID"]))
	$arGadgetParams["EVENT_ID"] = array($arGadgetParams["EVENT_ID"]);
	
if (in_array("", $arGadgetParams["EVENT_ID"]) || empty($arGadgetParams["EVENT_ID"]))
	$arGadgetParams["EVENT_ID"] = array("all");
else
{
	foreach($arGadgetParams["EVENT_ID"] as $i => $event_id_tmp)
	{
		if (!in_array($event_id_tmp, array("system", "system_groups", "system_friends", "forum", "photo", "blog", "tasks", "files", "calendar")))
			unset($arGadgetParams["EVENT_ID"][$i]);
		elseif (
			!CSocNetUser::IsFriendsAllowed()
			&& $event_id_tmp == "system_friends"
		)
			unset($arGadgetParams["EVENT_ID"][$i]);
		elseif (
			$arGadgetParams["ENTITY_TYPE"] == SONET_ENTITY_GROUP 
			&& in_array($event_id_tmp, array("system_groups", "system_friends"))
		)
			unset($arGadgetParams["EVENT_ID"][$i]);
	}
}

if ($arGadgetParams["ENTITY_TYPE"] == SONET_ENTITY_GROUP)
	$sTitle_1 = GetMessage('GD_LOG_GROUP');
elseif ($arGadgetParams["ENTITY_TYPE"] == SONET_ENTITY_USER)
	$sTitle_1 = GetMessage('GD_LOG_USER');
else
	$sTitle_1 = GetMessage('GD_LOG_ALL');

$sTitle_2 = "";
$arTitle_2 = array();

foreach ($arGadgetParams["EVENT_ID"] as $event_id_tmp)
{
	if ($event_id_tmp == "system")
		$arTitle_2[] = GetMessage('GD_LOG_SYSTEM');
	elseif ($event_id_tmp == "system_groups")
		$arTitle_2[] = GetMessage('GD_LOG_SYSTEM_GROUPS');
	elseif ($event_id_tmp == "system_friends")
		$arTitle_2[] = GetMessage('GD_LOG_SYSTEM_FRIENDS');
	elseif ($event_id_tmp == "forum" && $arGadgetParams["ENTITY_TYPE"] == SONET_ENTITY_GROUP)
		$arTitle_2[] = GetMessage('GD_LOG_FORUM_GROUP');
	elseif ($event_id_tmp == "forum" && $arGadgetParams["ENTITY_TYPE"] == SONET_ENTITY_USER)
		$arTitle_2[] = GetMessage('GD_LOG_FORUM_USER');
	elseif ($event_id_tmp == "forum")
		$arTitle_2[] = GetMessage('GD_LOG_FORUM');
	elseif ($event_id_tmp == "blog" && $arGadgetParams["ENTITY_TYPE"] == SONET_ENTITY_GROUP)
		$arTitle_2[] = GetMessage('GD_LOG_BLOG_GROUP');
	elseif ($event_id_tmp == "blog" && $arGadgetParams["ENTITY_TYPE"] == SONET_ENTITY_USER)
		$arTitle_2[] = GetMessage('GD_LOG_BLOG_USER');
	elseif ($event_id_tmp == "blog")
		$arTitle_2[] = GetMessage('GD_LOG_BLOG');
	elseif ($event_id_tmp == "tasks")
		$arTitle_2[] = GetMessage('GD_LOG_TASKS');
	elseif ($event_id_tmp == "calendar")
		$arTitle_2[] = GetMessage('GD_LOG_CALENDAR');
	elseif ($event_id_tmp == "photo")
		$arTitle_2[] = GetMessage('GD_LOG_PHOTO');
	elseif ($event_id_tmp == "files")
		$arTitle_2[] = GetMessage('GD_LOG_FILES');
}
$sTitle_2 = implode("/", $arTitle_2);

if (
	strlen($arGadgetParams["ENTITY_TYPE"]) > 0 
	|| (!is_array($arGadgetParams["EVENT_ID"]) && strlen($arGadgetParams["EVENT_ID"]) > 0 && $arGadgetParams["EVENT_ID"] != "all")
	|| (is_array($arGadgetParams["EVENT_ID"]) && count($arGadgetParams["EVENT_ID"]) > 0 && !in_array("all", $arGadgetParams["EVENT_ID"]))
)
{
	if (
		(!is_array($arGadgetParams["EVENT_ID"]) && (strlen($arGadgetParams["EVENT_ID"]) == 0 || $arGadgetParams["EVENT_ID"] == "all"))
		|| (is_array($arGadgetParams["EVENT_ID"]) && (count($arGadgetParams["EVENT_ID"]) == 0 || in_array("all", $arGadgetParams["EVENT_ID"])))
	)
		$arGadget["TITLE"] .= " [".$sTitle_1."]";
	elseif (strlen($arGadgetParams["ENTITY_TYPE"]) == 0)
		$arGadget["TITLE"] .= " [".$sTitle_2."]";
	else
		$arGadget["TITLE"] .= " [".$sTitle_1." - ".$sTitle_2."]";
}

$arGadgetParams["USER_VAR"] = ($arGadgetParams["USER_VAR"]?$arGadgetParams["USER_VAR"]:"user_id");
$arGadgetParams["GROUP_VAR"] = ($arGadgetParams["GROUP_VAR"]?$arGadgetParams["GROUP_VAR"]:"group_id");
$arGadgetParams["PAGE_VAR"] = ($arGadgetParams["PAGE_VAR"]?$arGadgetParams["PAGE_VAR"]:"page");
$arGadgetParams["PATH_TO_USER"] = ($arGadgetParams["PATH_TO_USER"]?$arGadgetParams["PATH_TO_USER"]:"/company/personal/user/#user_id#/");
$arGadgetParams["PATH_TO_GROUP"] = ($arGadgetParams["PATH_TO_GROUP"]?$arGadgetParams["PATH_TO_GROUP"]:"/workgroups/group/#group_id#/");
$arGadgetParams["LIST_URL"] = ($arGadgetParams["LIST_URL"]?$arGadgetParams["LIST_URL"]:"/company/personal/log/");

?>
<?

if($arGadgetParams["SHOW_TITLE"] == "Y"):
	?><h4><?= GetMessage("GD_LOG_TITLE") ?></h4><?
endif;

?><span class="show-where"><?
$APPLICATION->IncludeComponent(
	"bitrix:socialnetwork.log.ex",
	".default",
	Array(
		"ENTITY_TYPE" => $arGadgetParams["ENTITY_TYPE"],
		"GROUP_ID" => $arGadgetParams["GROUP_ID"],
		"EVENT_ID" => $arGadgetParams["EVENT_ID"],
		"USER_VAR" => $arGadgetParams["USER_VAR"],
		"GROUP_VAR" => $arGadgetParams["GROUP_VAR"],
		"PAGE_VAR" => $arGadgetParams["PAGE_VAR"],
		"PATH_TO_USER" => $arGadgetParams["PATH_TO_USER"],
		"PATH_TO_GROUP" => $arGadgetParams["PATH_TO_GROUP"],
		"SET_TITLE" => "N",
		"AUTH" => "N",
		"LOG_DATE_DAYS" => $arGadgetParams["LOG_DATE_DAYS"],
		"LOG_CNT" => ($arGadgetParams["LOG_CNT"] ? $arGadgetParams["LOG_CNT"] : 7),
		"SET_NAV_CHAIN" => "N",
		"PATH_TO_MESSAGES_CHAT" => $arParams["PM_URL"],
		"PATH_TO_CONPANY_DEPARTMENT" => $arParams["PATH_TO_CONPANY_DEPARTMENT"],
		"PATH_TO_VIDEO_CALL" => $arParams["PATH_TO_VIDEO_CALL"],
		"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
		"SHOW_YEAR" => $arParams["SHOW_YEAR"],
		"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
		"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
		"SUBSCRIBE_ONLY" => "Y",
		"SHOW_NAV_STRING" => "N",
		"SHOW_EVENT_ID_FILTER" => "N",
		"USE_COMMENTS" => "Y",
		"PHOTO_THUMBNAIL_SIZE" => "48",
		"AVATAR_SIZE" => $arGadgetParams["AVATAR_SIZE"],
		"AVATAR_SIZE_COMMENT" => $arGadgetParams["AVATAR_SIZE_COMMENT"],
		"PAGE_SIZE" => ($arGadgetParams["LOG_CNT"] ? $arGadgetParams["LOG_CNT"] : 7),
		"SHOW_RATING" => $arParams["SHOW_RATING"],
		"HIDE_EDIT_FORM" => "Y"
	),
	$component,
	Array("HIDE_ICONS"=>"Y")
);
?></span><?

if(strlen($arGadgetParams["LIST_URL"])>0):
	?><br />
	<div align="right"><a href="<?=htmlspecialcharsbx($arGadgetParams["LIST_URL"])?>"><?echo GetMessage("GD_LOG_MORE")?></a> <a href="<?=htmlspecialcharsbx($arGadgetParams["LIST_URL"])?>"><img width="7" height="7" border="0" src="/images/icons/arrows.gif" /></a>
	<br />
	</div><?
endif?>