<?
if (!CModule::IncludeModule('iblock'))
	return false;

$GLOBALS['INTR_DEPARTMENTS_CACHE'] = array();
$GLOBALS['INTR_DEPARTMENTS_CACHE_VALUE'] = array();
$GLOBALS['CACHE_HONOUR'] = null;
$GLOBALS['CACHE_ABSENCE'] = null;

define('BX_INTRANET_ABSENCE_HR', 0x1);
define('BX_INTRANET_ABSENCE_PERSONAL', 0x2);
define('BX_INTRANET_ABSENCE_ALL', BX_INTRANET_ABSENCE_HR|BX_INTRANET_ABSENCE_PERSONAL);

define('BX_INTRANET_SP_MAX_ERRORS', 3);
define('BX_INTRANET_SP_LOCK_TIME', 300);
define('BX_INTRANET_SP_QUEUE_COUNT', 5);
define('BX_INTRANET_SP_QUEUE_COUNT_MANUAL', 50);
define('BX_INTRANET_SP_LOG_COUNT', 3);
define('BX_INTRANET_SP_LOG_COUNT_MANUAL', 30);
define('BX_INTRANET_SP_NUM_ROWS_AUTO', 50);
define('BX_INTRANET_SP_NUM_ROWS_MANUAL', 100);

define('BX_INTRANET_SP_UF_NAME', 'UF_SP_ID');

define('SONET_INTRANET_NEW_USER_ENTITY', 'IN');
define('SONET_INTRANET_NEW_USER_EVENT_ID', 'intranet_new_user');
define('SONET_INTRANET_NEW_USER_COMMENT_EVENT_ID', 'intranet_new_user_comment');

CModule::AddAutoloadClasses(
	"intranet",
	array(
		"CIntranetTime" => "classes/general/time.php",
		"CIntranetUtils" => "classes/general/utils.php",
		"CIntranetSearch" => "tools/search.php",
		"CIntranetSearchConverter" => "tools/search.php",
		"CIntranetSearchConverters" => "tools/search.php",
		"CIntranetNotify" => "classes/general/notify.php",
		"CEventCalendar" => "classes/general/event_calendar.php",
		"CIntranetCalendarWS" => "classes/general/ws_calendar.php",
		"CIntranetContactsWS" => "classes/general/ws_contacts.php",
		"CIntranetRestService" => "classes/general/rest.php",
		"CIntranetToolbar" => "classes/general/toolbar.php",
		"CIntranetSharepoint" => "classes/mysql/sharepoint.php",
		"CIntranetSharepointQueue" => "classes/mysql/sharepoint_queue.php",
		"CIntranetSharepointLog" => "classes/mysql/sharepoint_log.php",
		"CIntranetAuthProvider" => "classes/general/authproviders.php",
		"CRatingRulesIntranet" => "classes/general/rating_rules.php",
		"CRatingsComponentsIntranet" => "classes/mysql/ratings_components.php",
		"CIntranetPlanner" => "classes/general/planner.php",
		"CIntranetInviteDialog" => "classes/general/invite_dialog.php",
		"CIntranetEventHandlers" => "classes/general/handlers.php",
		"CIEmployeeProperty" => "properties.php",
		"CUserTypeEmployee" => "properties.php",
		"CIBlockPropertyEmployee" => "properties.php",
	)
);

//loads custom language messages for organization types
CIntranetUtils::LoadCustomMessages();

$GLOBALS['INTRANET_TOOLBAR'] = new CIntranetToolbar();

CJSCore::RegisterExt('intranet_structure', array(
	'js' => '/bitrix/js/intranet/structure.js',
	'css' => [
		'/bitrix/js/intranet/intranet-common.css'
	],
	'lang' => '/bitrix/modules/intranet/lang/'.LANGUAGE_ID.'/js_core_intranet_structure.php',
	'rel' => ['ajax', 'popup', 'ui.forms', 'ui.design-tokens']
));

CJSCore::RegisterExt('planner', array(
	'js' => '/bitrix/js/intranet/core_planner.js',
	'css' => '/bitrix/js/intranet/core_planner.css',
	'lang' => '/bitrix/modules/intranet/lang/'.LANGUAGE_ID.'/js_core_intranet_planner.php',
	'rel' => array('date', 'ui.design-tokens')
));

CJSCore::RegisterExt("intranet_notify_dialog", array(
	"js" => "/bitrix/js/intranet/notify_dialog/notify_dialog.js",
	"css" => [
		'/bitrix/js/intranet/intranet-common.css',
		'/bitrix/js/intranet/notify_dialog/notify_dialog.css'
	],
	"lang" => "/bitrix/modules/intranet/lang/".LANGUAGE_ID."/install/js/notify_dialog.php",
	"rel" => array("popup")
));

CJSCore::RegisterExt("intranet_userfield_employee", array(
	"js" => "/bitrix/js/intranet/userfieldemployee.js",
	"css" => "/bitrix/js/intranet/userfieldemployee.css",
	"rel" => array('ui', 'ui.selector'),
));

CJSCore::RegisterExt("intranet_theme_picker", array(
	"js" => array("/bitrix/js/intranet/theme_picker/theme_picker.js"),
	"css" => "/bitrix/js/intranet/theme_picker/theme_picker.css",
	"lang" => "/bitrix/modules/intranet/install/js/theme_picker.php",
	"rel" => array("popup", "color_picker", "ajax", "fx", "ui.design-tokens", "ui.fonts.opensans"),
	"bundle_js" => "intranet_theme_picker",
	"bundle_css" => "intranet_theme_picker",
));

CJSCore::RegisterExt("sidepanel_bitrix24", ["rel" => ["intranet.sidepanel.bitrix24"]]);

/*patchlimitationmutatormark1*/

if(
	(!defined("ADMIN_SECTION") || ADMIN_SECTION !== true)
	&& (
		!CModule::IncludeModule('landing')
		|| !defined("SITE_TEMPLATE_ID")
		|| SITE_TEMPLATE_ID !== 'pub'
	)
)
{
	$GLOBALS["APPLICATION"]->SetAdditionalCSS("/bitrix/js/intranet/intranet-common.css");
}

