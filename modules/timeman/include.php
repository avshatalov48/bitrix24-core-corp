<?
if (!CModule::IncludeModule('intranet'))
	return false;

IncludeModuleLangFile(__FILE__);
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/timeman/install/index.php");

define('BX_TIMEMAN_ALLOWED_TIME_DELTA', 120); // allowed time delta - two minutes
define('BX_TIMEMAN_WRONG_DATE_CHECK', 86400);

define("SONET_TIMEMAN_ENTRY_ENTITY", "T");
define("SONET_WORK_REPORT_ENTITY", "R");

if (!defined("CACHED_timeman_settings"))
	define("CACHED_timeman_settings", 2592000);

$GLOBALS['BX_TIMEMAN_TASKS_MIGRATION_RULES'] = array(
	1 => array(2,7),
	2 => array(3,4),
	3 => array(4,6),
	4 => array(2),
	5 => array(2),
	6 => array(4,2),
);
$GLOBALS['BX_TIMEMAN_RECENTLY_ADDED_TASK_ID'] = null;
$GLOBALS['BX_TIMEMAN_RECENTLY_ADDED_EVENT_ID'] = null;

CModule::AddAutoloadClasses(
	"timeman",
	array(
		"CTimeMan" => "classes/general/timeman.php",
		"CTimeManCalendar" => "classes/general/timeman.php",
		"CTimeManUser" => "classes/general/timeman_user.php",
		"CTimeManEntry" => "classes/mysql/timeman_entry.php",
		"CTimeManReport" => "classes/mysql/timeman_report.php",
		"CTimeManReportDaily" => "classes/mysql/timeman_report_daily.php",
		"CTimeManReportFull" => "classes/general/timeman_report_full.php",
		"CUserReportFull" => "classes/general/timeman_report_full.php",
		"CReportSettings" => "classes/general/timeman_report_full.php",
		"CReportNotifications" => "classes/general/timeman_report_full.php",
		"CTimeManTableSchema" => "classes/general/timeman_table_schema.php",

		"CTimeManAdminReport" => "classes/general/timeman_admin_report.php",
		"CTimeManNotify" => "classes/general/timeman_notify.php",
		"CTimemanNotifySchema" => "classes/general/timeman_notify_schema.php",
	)
);

CJSCore::RegisterExt('timeman', array(
	'js' => '/bitrix/js/timeman/timemanager/core_timeman.js',
	'css' => array(
		'/bitrix/js/timeman/timemanager/css/core_timeman.css',
		'/bitrix/themes/.default/clock.css'
	),
	'lang' => '/bitrix/modules/timeman/lang/'.LANGUAGE_ID.'/js_core_timeman.php',
	'rel' => ['ui.design-tokens', 'ui.fonts.opensans', 'ajax', 'timer', 'popup', 'ls', 'planner', 'ui.layout-form'],
));

CJSCore::RegisterExt('timecontrol', array(
	'js' => '/bitrix/js/timeman/timecontrol/core_timecontrol.js',
	'css' => '/bitrix/js/timeman/timecontrol/css/core_timecontrol.css',
	'lang' => '/bitrix/modules/timeman/lang/'.LANGUAGE_ID.'/js_core_timecontrol.php',
	'rel' => array('ui.design-tokens', 'rest', 'popup', 'date', 'ls')
));

\Bitrix\Main\Page\Asset::getInstance()->groupJs('calendar_planner_handler', 'timeman');
\Bitrix\Main\Page\Asset::getInstance()->groupCss('calendar_planner_handler', 'timeman');

?>