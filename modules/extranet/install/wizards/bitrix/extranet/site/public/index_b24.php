<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

use Bitrix\Extranet\Service\ServiceContainer;
use Bitrix\Intranet;
use Bitrix\Main\Engine\CurrentUser;

if (!\CModule::IncludeModule('intranet'))
{
	return;
}

if (ServiceContainer::getInstance()->getCollaberService()->isCollaberById((int)CurrentUser::get()->getId()))
{
	LocalRedirect(SITE_DIR . 'online/');
}

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/extranet/public/index_b24.php');

$APPLICATION->SetPageProperty("NOT_SHOW_NAV_CHAIN", "Y");
$APPLICATION->SetPageProperty("title", htmlspecialcharsbx(COption::GetOptionString("main", "site_name", GetMessage("EXTRANET_INDEXB24_PAGE_TITLE"))));
?>
<?php
$APPLICATION->IncludeComponent(
	"bitrix:socialnetwork.log.ex",
	"",
	Array(
		"PATH_TO_SEARCH_TAG" => SITE_DIR."search/?tags=#tag#",
		"SET_NAV_CHAIN" => "Y",
		"SET_TITLE" => "Y",
		"ITEMS_COUNT" => "32",
		"NAME_TEMPLATE" => CSite::GetNameFormat(),
		"SHOW_LOGIN" => "Y",
		"DATE_TIME_FORMAT" => "#DATE_TIME_FORMAT#",
		"SHOW_YEAR" => "M",
		"CACHE_TYPE" => "A",
		"CACHE_TIME" => "3600",
		"SHOW_EVENT_ID_FILTER" => "Y",
		"SHOW_SETTINGS_LINK" => "Y",
		"SET_LOG_CACHE" => "Y",
		"USE_COMMENTS" => "Y",
		"BLOG_ALLOW_POST_CODE" => "Y",
		"BLOG_GROUP_ID" => Intranet\Integration\Wizards\Portal\Ids::getBlogId(),
		"PHOTO_USER_IBLOCK_TYPE" => "photos",
		"PHOTO_USER_IBLOCK_ID" => Intranet\Integration\Wizards\Portal\Ids::getIblockId('user_photogallery'),
		"PHOTO_USE_COMMENTS" => "Y",
		"PHOTO_COMMENTS_TYPE" => "FORUM",
		"PHOTO_FORUM_ID" => Intranet\Integration\Wizards\Portal\Ids::getForumId('PHOTOGALLERY_COMMENTS'),
		"PHOTO_USE_CAPTCHA" => "N",
		"FORUM_ID" => Intranet\Integration\Wizards\Portal\Ids::getForumId('USERS_AND_GROUPS'),
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
		"AVATAR_SIZE" => 50,
		"AVATAR_SIZE_COMMENT" => 39,
		"AUTH" => "Y",
	)
);
?>
<?php
if(CModule::IncludeModule('calendar')):
	$APPLICATION->IncludeComponent("bitrix:calendar.events.list", "widget", array(
		"CALENDAR_TYPE" => "user",
		"B_CUR_USER_LIST" => "Y",
		"INIT_DATE" => "",
		"FUTURE_MONTH_COUNT" => "1",
		"DETAIL_URL" => SITE_DIR . "contacts/personal/user/#user_id#/calendar/",
		"EVENTS_COUNT" => "10",
		"CACHE_TYPE" => "N",
		"CACHE_TIME" => "3600"
		),
		false
	);
endif;?>


<?php
if (CModule::IncludeModule('tasks')):
	$APPLICATION->IncludeComponent(
		"bitrix:tasks.widget.rolesfilter",
		"",
		[
			"USER_ID" => $USER->GetID(),
			"PATH_TO_TASKS" => SITE_DIR . "contacts/personal/user/".$USER->GetID()."/tasks/",
			"PATH_TO_TASKS_CREATE" => SITE_DIR . "contacts/personal/user/".$USER->GetID()."/tasks/task/edit/0/",
		],
		null,
		["HIDE_ICONS" => "N"]
	);
endif;
?>

<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");