<?
if(!CModule::IncludeModule('intranet'))
	return false;

IncludeModuleLangFile(__FILE__);

define('MEETING_COMMENTS_ENTITY_TYPE', 'ME');
define('MEETING_ITEMS_COMMENTS_ENTITY_TYPE', 'MI');

global $DBType;
CModule::AddAutoloadClasses(
	"meeting",
	array(
		"CMeeting" => "classes/".$DBType."/meeting.php",
		"CMeetingItem" => "classes/".$DBType."/meeting_item.php",
		"CMeetingInstance" => "classes/".$DBType."/meeting_item_instance.php",
		"CMeetingReports" => "classes/".$DBType."/meeting_item_instance_reports.php",

		"CMeetingEventHandlers" => "classes/general/events.php",
		"CMeetingForumHandlers" => "classes/general/events.php",
		"CMeetingItemForumHandlers" => "classes/general/events.php",
	)
);

CJSCore::RegisterExt('meeting', array(
	'js' => '/bitrix/js/meeting/meeting.js',
	'css' => '/bitrix/js/meeting/css/meetings.css',
	'lang' => BX_ROOT.'/modules/meeting/lang/'.LANGUAGE_ID.'/js_meeting.php',
));
?>
