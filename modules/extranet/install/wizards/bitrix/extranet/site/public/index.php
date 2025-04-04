<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

if (!\CModule::IncludeModule('intranet') || !\CModule::IncludeModule('extranet'))
{
	return;
}

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/extranet/public/index.php');

$APPLICATION->SetTitle(GetMessage("EXTRANET_INDEX_PAGE_TITLE"));

use Bitrix\Intranet;

?>
<p><table class="data-table">
<tr>
<td>
<?= GetMessage("EXTRANET_INDEX_PAGE_TEXT1") ?>
</td>
</tr>
</table>
<p><?php
$APPLICATION->IncludeComponent("bitrix:desktop", ".default", array(
	"ID" => "dashboard_external",
	"CAN_EDIT" => "Y",
	"COLUMNS" => "3",
	"COLUMN_WIDTH_0" => "25%",
	"COLUMN_WIDTH_1" => "50%",
	"COLUMN_WIDTH_2" => "25%",
	"PATH_TO_VIDEO_CALL" => SITE_DIR . "contacts/personal/video/#USER_ID#/",
	"GADGETS" => array(
		0 => "RSSREADER",
		1 => "FAVORITES",
		2 => "EXTRANET_CONTACTS",
		3 => "TASKS",
		4 => "CALENDAR",
		5 => "PROFILE",
		6 => "UPDATES",
		7 => "WORKGROUPS",
	),
	"G_RSSREADER_CACHE_TIME" => "3600",
	"G_RSSREADER_SHOW_URL" => "Y",
	"G_RSSREADER_PREDEFINED_RSS" => "#ABOUT_LIFE_RSS#",
	"GU_RSSREADER_CNT" => "5",
	"GU_RSSREADER_RSS_URL" => "#ABOUT_LIFE_RSS#",
	"G_EXTRANET_CONTACTS_DETAIL_URL" => SITE_DIR . "contacts/personal/user/#ID#/",
	"G_EXTRANET_CONTACTS_MESSAGES_CHAT_URL" => SITE_DIR . "contacts/personal/messages/chat/#ID#/",
	"G_EXTRANET_CONTACTS_FULLLIST_URL" => SITE_DIR . "contacts/",
	"G_EXTRANET_CONTACTS_FULLLIST_EMPLOYEES_URL" => SITE_DIR . "contacts/employees.php",
	"GU_EXTRANET_CONTACTS_MY_WORKGROUPS_USERS_COUNT" => "5",
	"GU_EXTRANET_CONTACTS_PUBLIC_USERS_COUNT" => "5",
	"G_TASKS_IBLOCK_ID" => "#TASKS_IBLOCK_ID#",
	"G_TASKS_PAGE_VAR" => "page",
	"G_TASKS_GROUP_VAR" => "group_id",
	"G_TASKS_VIEW_VAR" => "user_id",
	"G_TASKS_TASK_VAR" => "task_id",
	"G_TASKS_ACTION_VAR" => "action",
	"G_TASKS_PATH_TO_GROUP_TASKS" => SITE_DIR . "workgroups/group/#group_id#/tasks/",
	"G_TASKS_PATH_TO_GROUP_TASKS_TASK" => SITE_DIR . "workgroups/group/#group_id#/tasks/task/#action#/#task_id#/",
	"G_TASKS_PATH_TO_USER_TASKS" => SITE_DIR . "contacts/personal/user/#user_id#/tasks/",
	"G_TASKS_PATH_TO_USER_TASKS_TASK" => SITE_DIR . "contacts/personal/user/#user_id#/tasks/task/#action#/#task_id#/",
	"G_TASKS_PATH_TO_TASK" => SITE_DIR . "contacts/personal/user/#user_id#/tasks/",
	"G_TASKS_PATH_TO_TASK_NEW" => SITE_DIR . "contacts/personal/user/#user_id#/tasks/task/create/0/",
	"GU_TASKS_ITEMS_COUNT" => "20",
	"GU_TASKS_ORDER_BY" => "E",
	"GU_TASKS_TYPE" => "Z",
	"G_CALENDAR_IBLOCK_TYPE" => "events",
	"G_CALENDAR_IBLOCK_ID" => Intranet\Integration\Wizards\Portal\Ids::getIblockId('calendar_employees'),
	"G_CALENDAR_DETAIL_URL" => SITE_DIR . "contacts/personal/user/#user_id#/calendar/",
	"G_CALENDAR_CACHE_TYPE" => "N",
	"G_CALENDAR_CACHE_TIME" => "3600",
	"G_CALENDAR_CALENDAR_URL" => SITE_DIR . "contacts/personal/user/#user_id#/calendar/",
	"GU_CALENDAR_EVENTS_COUNT" => "5",
	"G_PROFILE_PATH_TO_GENERAL" => SITE_DIR . "contacts/personal/",
	"G_PROFILE_PATH_TO_PROFILE_EDIT" => SITE_DIR . "contacts/personal/user/#user_id#/edit/",
	"G_PROFILE_PATH_TO_LOG" => SITE_DIR . "contacts/personal/log/",
	"G_PROFILE_PATH_TO_SUBSCR" => SITE_DIR . "contacts/personal/subscribe/",
	"G_PROFILE_PATH_TO_MSG" => SITE_DIR . "contacts/personal/messages/",
	"G_PROFILE_PATH_TO_GROUPS" => SITE_DIR . "contacts/personal/user/#user_id#/groups/",
	"G_PROFILE_PATH_TO_GROUP_NEW" => SITE_DIR . "contacts/personal/user/#user_id#/groups/create/",
	"G_PROFILE_PATH_TO_PHOTO" => SITE_DIR . "contacts/personal/user/#user_id#/photo/",
	"G_PROFILE_PATH_TO_PHOTO_NEW" => SITE_DIR . "contacts/personal/user/#user_id#/photo/photo/user_#user_id#/0/action/upload/",
	"G_PROFILE_PATH_TO_FORUM" => SITE_DIR . "contacts/personal/user/#user_id#/forum/",
	"G_PROFILE_PATH_TO_BLOG" => SITE_DIR . "contacts/personal/user/#user_id#/blog/",
	"G_PROFILE_PATH_TO_BLOG_NEW" => SITE_DIR . "contacts/personal/user/#user_id#/blog/edit/new/",
	"G_PROFILE_PATH_TO_CAL" => SITE_DIR . "contacts/personal/user/#user_id#/calendar/",
	"G_PROFILE_PATH_TO_TASK" => SITE_DIR . "contacts/personal/user/#user_id#/tasks/",
	"G_PROFILE_PATH_TO_TASK_NEW" => SITE_DIR . "contacts/personal/user/#user_id#/tasks/task/create/0/",
	"G_PROFILE_PATH_TO_LIB" => SITE_DIR . "contacts/personal/user/#user_id#/files/lib/",
	"GU_PROFILE_SHOW_GENERAL" => "Y",
	"GU_PROFILE_SHOW_GROUPS" => "Y",
	"GU_PROFILE_SHOW_PHOTO" => "Y",
	"GU_PROFILE_SHOW_FORUM" => "Y",
	"GU_PROFILE_SHOW_CAL" => "Y",
	"GU_PROFILE_SHOW_BLOG" => "Y",
	"GU_PROFILE_SHOW_TASK" => "Y",
	"GU_PROFILE_SHOW_LIB" => "Y",
	"G_UPDATES_USER_VAR" => "user_id",
	"G_UPDATES_GROUP_VAR" => "group_id",
	"G_UPDATES_PAGE_VAR" => "page",
	"G_UPDATES_PATH_TO_USER" => SITE_DIR . "contacts/personal/user/#user_id#/",
	"G_UPDATES_PATH_TO_GROUP" => SITE_DIR . "workgroups/group/#group_id#/",
	"G_UPDATES_LIST_URL" => SITE_DIR . "contacts/personal/log/",
	"GU_UPDATES_ENTITY_TYPE" => "",
	"GU_UPDATES_EVENT_ID" => "",
	"G_WORKGROUPS_GROUP_VAR" => "group_id",
	"G_WORKGROUPS_PATH_TO_GROUP" => SITE_DIR . "workgroups/group/#group_id#/",
	"G_WORKGROUPS_PATH_TO_GROUP_SEARCH" => SITE_DIR . "workgroups/",
	"G_WORKGROUPS_CACHE_TIME" => "3600",
	"GU_WORKGROUPS_DISPLAY_PICTURE" => "Y",
	"GU_WORKGROUPS_DISPLAY_DESCRIPTION" => "Y",
	"GU_WORKGROUPS_DISPLAY_NUMBER_OF_MEMBERS" => "Y",
	"GU_WORKGROUPS_FILTER_MY" => "Y"
	),
	false
);?></p>
<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
