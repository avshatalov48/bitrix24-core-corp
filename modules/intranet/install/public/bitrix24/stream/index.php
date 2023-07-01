<?php

/**
 * @global  \CMain $APPLICATION
 * @global  \CUser $USER
 */

use Bitrix\Intranet\Integration\Wizards\Portal\Ids;
use Bitrix\Main\Loader;

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/intranet/public_bitrix24/index.php');

$APPLICATION->SetPageProperty('NOT_SHOW_NAV_CHAIN', 'Y');
$APPLICATION->SetPageProperty('title', htmlspecialcharsbx(COption::GetOptionString('main', 'site_name', 'Bitrix24')));
Loader::includeModule('intranet');

GetGlobalID();

$componentDateTimeFormat = CIntranetUtils::getCurrentDateTimeFormat();

$APPLICATION->IncludeComponent(
	"bitrix:socialnetwork.log.ex",
	"",
	Array(
		"PATH_TO_LOG_ENTRY" => "/company/personal/log/#log_id#/",
		"PATH_TO_USER" => "/company/personal/user/#user_id#/",
		"PATH_TO_MESSAGES_CHAT" => "/company/personal/messages/chat/#user_id#/",
		"PATH_TO_VIDEO_CALL" => "/company/personal/video/#user_id#/",
		"PATH_TO_GROUP" => "/workgroups/group/#group_id#/",
		"PATH_TO_SMILE" => "/bitrix/images/socialnetwork/smile/",
		"PATH_TO_USER_MICROBLOG" => "/company/personal/user/#user_id#/blog/",
		"PATH_TO_GROUP_MICROBLOG" => "/workgroups/group/#group_id#/blog/",
		"PATH_TO_USER_BLOG_POST" => "/company/personal/user/#user_id#/blog/#post_id#/",
		"PATH_TO_USER_MICROBLOG_POST" => "/company/personal/user/#user_id#/blog/#post_id#/",
		"PATH_TO_USER_BLOG_POST_EDIT" => "/company/personal/user/#user_id#/blog/edit/#post_id#/",
		"PATH_TO_USER_BLOG_POST_IMPORTANT" => "/company/personal/user/#user_id#/blog/important/",
		"PATH_TO_GROUP_BLOG_POST" => "/workgroups/group/#group_id#/blog/#post_id#/",
		"PATH_TO_GROUP_MICROBLOG_POST" => "/workgroups/group/#group_id#/blog/#post_id#/",
		"PATH_TO_USER_PHOTO" => "/company/personal/user/#user_id#/photo/",
		"PATH_TO_GROUP_PHOTO" => "/workgroups/group/#group_id#/photo/",
		"PATH_TO_USER_PHOTO_SECTION" => "/company/personal/user/#user_id#/photo/album/#section_id#/",
		"PATH_TO_GROUP_PHOTO_SECTION" => "/workgroups/group/#group_id#/photo/album/#section_id#/",
		"PATH_TO_USER_PHOTO_ELEMENT" => "/company/personal/user/#user_id#/photo/photo/#section_id#/#element_id#/",
		"PATH_TO_GROUP_PHOTO_ELEMENT" => "/workgroups/group/#group_id#/photo/#section_id#/#element_id#/",
		"PATH_TO_SEARCH_TAG" => "/search/?tags=#tag#",
		"SET_NAV_CHAIN" => "Y",
		"SET_TITLE" => "Y",
		"ITEMS_COUNT" => "32",
		"NAME_TEMPLATE" => "",
		"SHOW_LOGIN" => "Y",
		"DATE_TIME_FORMAT" => $componentDateTimeFormat,
		"DATE_TIME_FORMAT_WITHOUT_YEAR" => CIntranetUtils::getCurrentDateTimeFormat(array(
			'woYear' => true
		)),
		"SHOW_YEAR" => "M",
		"CACHE_TYPE" => "A",
		"CACHE_TIME" => "3600",
		"PATH_TO_CONPANY_DEPARTMENT" => "/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#",
		"SHOW_EVENT_ID_FILTER" => "Y",
		"SHOW_SETTINGS_LINK" => "Y",
		"SET_LOG_CACHE" => "Y",
		"USE_COMMENTS" => "Y",
		"BLOG_ALLOW_POST_CODE" => "Y",
		"BLOG_GROUP_ID" => Ids::getBlogId(),
		"PHOTO_USER_IBLOCK_TYPE" => "photos",
		"PHOTO_USER_IBLOCK_ID" => Ids::getIblockId('user_photogallery'),
		"PHOTO_USE_COMMENTS" => "Y",
		"PHOTO_COMMENTS_TYPE" => "FORUM",
		"PHOTO_FORUM_ID" => Ids::getForumId('PHOTOGALLERY_COMMENTS'),
		"PHOTO_USE_CAPTCHA" => "N",
		"FORUM_ID" => Ids::getForumId('USERS_AND_GROUPS'),
		"PAGER_DESC_NUMBERING" => "N",
		"AJAX_MODE" => "N",
		"AJAX_OPTION_SHADOW" => "N",
		"AJAX_OPTION_HISTORY" => "N",
		"AJAX_OPTION_JUMP" => "N",
		"AJAX_OPTION_STYLE" => "Y",
		"CONTAINER_ID" => "log_external_container",
		"SHOW_RATING" => "",
		"RATING_TYPE" => "",
		"NEW_TEMPLATE" => "Y",
		"AVATAR_SIZE" => 100,
		"AVATAR_SIZE_COMMENT" => 100,
		"AVATAR_SIZE_COMMON" => 100,
		"AUTH" => "Y",
	)
);

if (Loader::includeModule('intranet'))
{
	$APPLICATION->IncludeComponent('bitrix:intranet.ustat.online', '', [], false);
	$APPLICATION->IncludeComponent('bitrix:intranet.ustat.status', '', ['CREATE_FRAME' => 'N'], false);
}

$APPLICATION->IncludeComponent(
	"bitrix:calendar.events.list",
	"widget",
	array(
		"CALENDAR_TYPE" => "user",
		"B_CUR_USER_LIST" => "Y",
		"INIT_DATE" => "",
		"FUTURE_MONTH_COUNT" => "1",
		"DETAIL_URL" => "/company/personal/user/#user_id#/calendar/",
		"EVENTS_COUNT" => "5",
		"CACHE_TYPE" => "N",
		"CACHE_TIME" => "3600"
	),
	false
);

$APPLICATION->IncludeComponent(
	"bitrix:tasks.widget.rolesfilter",
	"",
	[
		"USER_ID" => $USER->GetID(),
		"PATH_TO_TASKS" => "/company/personal/user/".$USER->GetID()."/tasks/",
		"PATH_TO_TASKS_CREATE" => "/company/personal/user/".$USER->GetID()."/tasks/task/edit/0/",
	],
	null,
	["HIDE_ICONS" => "N"]
);

if ($USER->IsAuthorized())
{
	$APPLICATION->IncludeComponent(
		"bitrix:socialnetwork.blog.blog",
		"important",
		Array(
			"BLOG_URL" => "",
			"FILTER" => array(
				"=UF_BLOG_POST_IMPRTNT" => 1,
				"!POST_PARAM_BLOG_POST_IMPRTNT" => array("USER_ID" => $USER->GetId(), "VALUE" => "Y")
			),
			"FILTER_NAME" => "",
			"YEAR" => "",
			"MONTH" => "",
			"DAY" => "",
			"CATEGORY_ID" => "",
			"GROUP_ID" => array(),
			"USER_ID" => $USER->GetId(),
			"SOCNET_GROUP_ID" => 0,
			"SORT" => array(),
			"SORT_BY1" => "",
			"SORT_ORDER1" => "",
			"SORT_BY2" => "",
			"SORT_ORDER2" => "",
			//************** Page settings **************************************
			"MESSAGE_COUNT" => 0,
			"NAV_TEMPLATE" => "",
			"PAGE_SETTINGS" => array("bDescPageNumbering" => false, "nPageSize" => 10),
			//************** URL ************************************************
			"BLOG_VAR" => "",
			"POST_VAR" => "",
			"USER_VAR" => "",
			"PAGE_VAR" => "",
			"PATH_TO_BLOG" => "/company/personal/user/#user_id#/blog/",
			"PATH_TO_BLOG_CATEGORY" => "",
			"PATH_TO_BLOG_POSTS" => "/company/personal/user/#user_id#/blog/important/",
			"PATH_TO_POST" => "/company/personal/user/#user_id#/blog/#post_id#/",
			"PATH_TO_POST_EDIT" => "/company/personal/user/#user_id#/blog/edit/#post_id#/",
			"PATH_TO_USER" => "/company/personal/user/#user_id#/",
			"PATH_TO_SMILE" => "/bitrix/images/socialnetwork/smile/",
			//************** ADDITIONAL *****************************************
			"DATE_TIME_FORMAT" => $componentDateTimeFormat,
			"NAME_TEMPLATE" => "",
			"SHOW_LOGIN" => "Y",
			"AVATAR_SIZE" => 100,
			"SET_TITLE" => "N",
			"SHOW_RATING" => "N",
			"RATING_TYPE" => "",
			//************** CACHE **********************************************
			"CACHE_TYPE" => "A",
			"CACHE_TIME" => 3600,
			"CACHE_TAGS" => array("IMPORTANT", "IMPORTANT".$USER->GetId()),
			//************** Template Settings **********************************
			"OPTIONS" => array(array("name" => "BLOG_POST_IMPRTNT", "value" => "Y")),
		),
		null
	);
}

$APPLICATION->IncludeComponent(
	"bitrix:blog.popular_posts",
	"widget",
	array(
		"GROUP_ID" => 1,
		"SORT_BY1" => "RATING_TOTAL_VALUE",
		"MESSAGE_COUNT" => "5",
		"PERIOD_DAYS" => "8",
		"MESSAGE_LENGTH" => "100",
		"DATE_TIME_FORMAT" => $componentDateTimeFormat,
		"PATH_TO_BLOG" => "/company/personal/user/#user_id#/blog/",
		"PATH_TO_GROUP_BLOG_POST" => "/workgroups/group/#group_id#/blog/#post_id#/",
		"PATH_TO_POST" => "/company/personal/user/#user_id#/blog/#post_id#/",
		"PATH_TO_USER" => "/company/personal/user/#user_id#/",
		"CACHE_TYPE" => "A",
		"CACHE_TIME" => "3600",
		"SEO_USER" => "Y",
		"USE_SOCNET" => "Y",
		"WIDGET_MODE" => "Y",
	),
	false
);

$APPLICATION->IncludeComponent(
	"bitrix:intranet.structure.birthday.nearest",
	"widget",
	array(
		"NUM_USERS" => "4",
		"NAME_TEMPLATE" => "",
		"SHOW_LOGIN" => "Y",
		"CACHE_TYPE" => "A",
		"CACHE_TIME" => "86450",
		"CACHE_DATE" => date('dmy'),
		"SHOW_YEAR" => "N",
		"DETAIL_URL" => "/company/personal/user/#USER_ID#/",
		"DEPARTMENT" => "0",
		"AJAX_OPTION_ADDITIONAL" => ""
	)
);

if (CModule::IncludeModule("bizproc") && CBPRuntime::isFeatureEnabled())
{
	$APPLICATION->IncludeComponent(
		"bitrix:bizproc.task.list",
		"widget",
		array(
			"COUNTERS_ONLY" => "Y",
			"USER_ID" => $USER->GetID(),
			"PATH_TO_BP_TASKS" => "/company/personal/bizproc/",
			"PATH_TO_MY_PROCESSES" => "/company/personal/processes/",
		),
		null,
		array("HIDE_ICONS" => "N")
	);
}

$APPLICATION->IncludeComponent(
	"bitrix:intranet.bitrix24.banner",
	"",
	array(),
	null,
	array("HIDE_ICONS" => "N")
);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
