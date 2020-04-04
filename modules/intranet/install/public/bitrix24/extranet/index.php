<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/extranet/index.php");
$APPLICATION->SetTitle(GetMessage("TITLE"));
GetGlobalID();
$componentDateTimeFormat = CIntranetUtils::getCurrentDateTimeFormat();

$APPLICATION->IncludeComponent(
	"bitrix:socialnetwork.log.ex", 
	"", 
	Array(
		"PATH_TO_LOG_ENTRY" => "/extranet/contacts/personal/log/#log_id#/",
		"PATH_TO_USER" => "/extranet/contacts/personal/user/#user_id#/",
		"PATH_TO_MESSAGES_CHAT" => "/extranet/contacts/personal/messages/chat/#user_id#/",
		"PATH_TO_VIDEO_CALL" => "/extranet/contacts/personal/video/#user_id#/",
		"PATH_TO_GROUP" => "/extranet/workgroups/group/#group_id#/",
		"PATH_TO_SMILE" => "/bitrix/images/socialnetwork/smile/",
		"PATH_TO_USER_MICROBLOG" => "/extranet/contacts/personal/user/#user_id#/blog/",
		"PATH_TO_GROUP_MICROBLOG" => "/extranet/workgroups/group/#group_id#/blog/",
		"PATH_TO_USER_BLOG_POST" => "/extranet/contacts/personal/user/#user_id#/blog/#post_id#/",
		"PATH_TO_USER_MICROBLOG_POST" => "/extranet/contacts/personal/user/#user_id#/blog/#post_id#/",
		"PATH_TO_USER_BLOG_POST_EDIT" => "/extranet/contacts/personal/user/#user_id#/blog/edit/#post_id#/",
		"PATH_TO_USER_BLOG_POST_IMPORTANT" => "/extranet/contacts/personal/user/#user_id#/blog/important/",
		"PATH_TO_GROUP_BLOG_POST" => "/extranet/workgroups/group/#group_id#/blog/#post_id#/",
		"PATH_TO_GROUP_MICROBLOG_POST" => "/extranet/workgroups/group/#group_id#/blog/#post_id#/",
		"PATH_TO_USER_PHOTO" => "/extranet/contacts/personal/user/#user_id#/photo/",
		"PATH_TO_GROUP_PHOTO" => "/extranet/workgroups/group/#group_id#/photo/",
		"PATH_TO_USER_PHOTO_SECTION" => "/extranet/contacts/personal/user/#user_id#/photo/album/#section_id#/",
		"PATH_TO_GROUP_PHOTO_SECTION" => "/extranet/workgroups/group/#group_id#/photo/album/#section_id#/",
		"PATH_TO_USER_PHOTO_ELEMENT" => "/extranet/contacts/personal/user/#user_id#/photo/photo/#section_id#/#element_id#/",		
		"PATH_TO_GROUP_PHOTO_ELEMENT" => "/extranet/workgroups/group/#group_id#/photo/#section_id#/#element_id#/",
		"PATH_TO_SEARCH_TAG" => "/extranet/search/?tags=#tag#",
		"SET_NAV_CHAIN" => "Y",
		"SET_TITLE" => "Y",
		"ITEMS_COUNT" => "32",
		"NAME_TEMPLATE" => CSite::GetNameFormat(),
		"SHOW_LOGIN" => "Y",
		"DATE_TIME_FORMAT" => $componentDateTimeFormat,
		"SHOW_YEAR" => "M",
		"CACHE_TYPE" => "A",
		"CACHE_TIME" => "3600",
		"SHOW_EVENT_ID_FILTER" => "Y",
		"SHOW_SETTINGS_LINK" => "Y",
		"SET_LOG_CACHE" => "Y",
		"USE_COMMENTS" => "Y",
		"BLOG_ALLOW_POST_CODE" => "Y",
		"BLOG_GROUP_ID" => $GLOBAL_BLOG_GROUP[SITE_ID],
		"PHOTO_USER_IBLOCK_TYPE" => "photos",
		"PHOTO_USER_IBLOCK_ID" => $GLOBAL_IBLOCK_ID["user_photogallery"],
		"PHOTO_USE_COMMENTS" => "Y",
		"PHOTO_COMMENTS_TYPE" => "FORUM",
		"PHOTO_FORUM_ID" => $GLOBAL_FORUM_ID["PHOTOGALLERY_COMMENTS"],
		"PHOTO_USE_CAPTCHA" => "N",
		"FORUM_ID" => $GLOBAL_FORUM_ID["USERS_AND_GROUPS"],
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
		"AUTH" => "Y",
	)
);?>
<?if ($GLOBALS["USER"]->IsAuthorized()){
	$APPLICATION->IncludeComponent(
		"bitrix:socialnetwork.blog.blog",
		"important",
		Array(
			"BLOG_URL" => "",
			"FILTER" => array(">UF_BLOG_POST_IMPRTNT" => 0, "!POST_PARAM_BLOG_POST_IMPRTNT" => array("USER_ID" => $GLOBALS["USER"]->GetId(), "VALUE" => "Y")),
			"FILTER_NAME" => "",
			"YEAR" => "",
			"MONTH" => "",
			"DAY" => "",
			"CATEGORY_ID" => "",
			"GROUP_ID" => array(),
			"USER_ID" => $GLOBALS["USER"]->GetId(),
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
			"PATH_TO_BLOG" => "/extranet/contacts/personal/user/#user_id#/blog/",
			"PATH_TO_BLOG_CATEGORY" => "",
			"PATH_TO_BLOG_POSTS" => "/extranet/contacts/personal/user/#user_id#/blog/important/",
			"PATH_TO_POST" => "/extranet/contacts/personal/user/#user_id#/blog/#post_id#/",
			"PATH_TO_POST_EDIT" => "/extranet/contacts/personal/user/#user_id#/blog/edit/#post_id#/",
			"PATH_TO_USER" => "/extranet/contacts/personal/user/#user_id#/",
			"PATH_TO_SMILE" => "/bitrix/images/socialnetwork/smile/",
			//************** ADDITIONAL *****************************************
			"DATE_TIME_FORMAT" => $componentDateTimeFormat,
			"NAME_TEMPLATE" => "",
			"SHOW_LOGIN" => "Y",
			"AVATAR_SIZE" => 42,
			"SET_TITLE" => "N",
			"SHOW_RATING" => "N",
			"RATING_TYPE" => "",
			//************** CACHE **********************************************
			"CACHE_TYPE" => "A",
			"CACHE_TIME" => 3600,
			"CACHE_TAGS" => array("IMPORTANT", "IMPORTANT".$GLOBALS["USER"]->GetId()),
			//************** Template Settings **********************************
			"OPTIONS" => array(array("name" => "BLOG_POST_IMPRTNT", "value" => "Y")),
		),
		null
	);
}?>
<?$APPLICATION->IncludeComponent(
	"bitrix:tasks.filter.v2",
	"widget",
	array(
		"VIEW_TYPE" => 0,
		"COMMON_FILTER" => array("ONLY_ROOT_TASKS" => "Y"),
		"USER_ID" => $USER->GetID(),
			"ROLE_FILTER_SUFFIX" => "",
			"PATH_TO_TASKS" => "/extranet/contacts/personal/user/".$USER->GetID()."/tasks/",
	),
	null,
	array("HIDE_ICONS" => "N")
);?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
