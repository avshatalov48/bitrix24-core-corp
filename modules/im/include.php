<?

use Bitrix\Main\Loader;

IncludeModuleLangFile(__FILE__);

/**
 * @use \Bitrix\Im\Revision::getWeb()
 * @deprecated
 */
define("IM_REVISION", 117);
/**
 * @use \Bitrix\Im\Revision::getMobile()
 * @deprecated
 */
define("IM_REVISION_MOBILE", 8); // sync with im.recent/im.dialog components

define("IM_MESSAGE_SYSTEM", "S");
define("IM_MESSAGE_PRIVATE", "P");
define("IM_MESSAGE_CHAT", "C");
define("IM_MESSAGE_OPEN", "O");
define("IM_MESSAGE_THREAD", "T");
define("IM_MESSAGE_OPEN_LINE", "L");

define("IM_CHAT_TYPE_PERSONAL", "PERSONAL");
/**
 * @use const IM_MESSAGE_CHAT
 * @deprecated
 */
define("IM_MESSAGE_GROUP", "C");

define("IM_NOTIFY_MESSAGE", 0);
define("IM_NOTIFY_CONFIRM", 1);
define("IM_NOTIFY_FROM", 2);
define("IM_NOTIFY_SYSTEM", 4);

define("IM_STATUS_UNREAD", 0);
define("IM_STATUS_NOTIFY", 1);
define("IM_STATUS_READ", 2);

define("IM_MESSAGE_STATUS_RECEIVED", 'received');
define("IM_MESSAGE_STATUS_ERROR", 'error');
define("IM_MESSAGE_STATUS_DELIVERED", 'delivered');

define("IM_CALL_NONE", 0);
define("IM_CALL_VIDEO", 1);
define("IM_CALL_AUDIO", 2);

define("IM_MAIL_SKIP", '#SKIP#');

define("IM_CALL_STATUS_NONE", 0);
define("IM_CALL_STATUS_WAIT", 1);
define("IM_CALL_STATUS_ANSWER", 2);
define("IM_CALL_STATUS_DECLINE", 3);

define("IM_CALL_END_BUSY", 'busy');
define("IM_CALL_END_DECLINE", 'decline');
define("IM_CALL_END_TIMEOUT", 'waitTimeout');
define("IM_CALL_END_ACCESS", 'errorAccess');
define("IM_CALL_END_OFFLINE", 'errorOffline');

define("IM_SPEED_NOTIFY", 1);
define("IM_SPEED_MESSAGE", 2);
define("IM_SPEED_GROUP", 3);

define("IM_CHECK_UPDATE", 'update');
define("IM_CHECK_DELETE", 'delete');

define("IM_DESKTOP_WINDOWS", 'windows');
define("IM_DESKTOP_MAC", 'mac');

define("IM_NOTIFY_FEATURE_SITE", "site");
define("IM_NOTIFY_FEATURE_XMPP", "xmpp");
define("IM_NOTIFY_FEATURE_MAIL", "mail");
define("IM_NOTIFY_FEATURE_PUSH", "push");

CModule::AddAutoloadClasses(
	"im",
	array(
		"CIMSettings" => "classes/general/im_settings.php",
		"CIMMessenger" => "classes/general/im_messenger.php",
		"CIMNotify" => "classes/general/im_notify.php",
		"CIMContactList" => "classes/mysql/im_contact_list.php",
		"CIMChat" => "classes/general/im_chat.php",
		"CIMMessage" => "classes/general/im_message.php",
		"CIMMessageLink" => "classes/general/im_message_param.php",
		"CIMMessageParam" => "classes/general/im_message_param.php",
		"CIMMessageParamAttach" => "classes/general/im_message_param.php",
		"CIMHistory" => "classes/general/im_history.php",
		"CIMEvent" => "classes/general/im_event.php",
		"CIMCall" => "classes/general/im_call.php",
		"CIMMail" => "classes/general/im_mail.php",
		"CIMConvert" => "classes/general/im_convert.php",
		"CIMHint" => "classes/general/im_hint.php",
		"CIMTableSchema" => "classes/general/im_table_schema.php",
		"CIMNotifySchema" => "classes/general/im_notify_schema.php",
		"CIMRestService" => "classes/general/im_rest.php",
		"DesktopApplication" => "classes/general/im_event.php",
		"CIMStatus" => "classes/general/im_status.php",
		"CIMDisk" => "classes/general/im_disk.php",
		"CIMShare" => "classes/general/im_share.php",
	)
);

$jsCoreRel = array('im_desktop_utils', 'resize_observer', 'im_common', 'im_phone_call_view', 'im.lib.localstorage', 'clipboard', 'sidepanel', 'loader', 'ui.notification', 'ui.alerts', 'ui.vue', 'ui.buttons', 'ui.switcher', 'ui.hint', 'im.application.notifications');
$jsCoreRelMobile = array('im_common', 'uploader', 'mobile.pull.client');
if (IsModuleInstalled('voximplant'))
{
	$jsCoreRel[] = 'voximplant';
	$jsCoreRelMobile[] = 'mobile_voximplant';
}
if (IsModuleInstalled('disk'))
{
	$jsCoreRel[] = 'file_dialog';
	$jsCoreRel[] = 'im.integration.viewer';
}
if (IsModuleInstalled('calendar'))
{
	$jsCoreRel[] = 'calendar.sliderloader';
}
if (IsModuleInstalled('pull'))
{
	$jsCoreRel[] = 'webrtc';
	$jsCoreRel[] = 'webrtc_adapter';
}
if (IsModuleInstalled('pull') || IsModuleInstalled('disk'))
{
	$jsCoreRel[] = 'uploader';
}

$jsCoreRelPage = $jsCoreRel;
$jsCoreRelPage[] = 'im_window';

$userAgent = \Bitrix\Main\Context::getCurrent()->getRequest()->getUserAgent();
/* TODO 2 tabs desktop
if (mb_strpos(mb_strtolower($userAgent), "bitrixdesktop") !== false)
{
	$jsCoreRelPage[] = 'im_desktop';
	$jsCoreRelPage[] = 'im_timecontrol';
}
*/

$jsImCall = [
	'/bitrix/js/im/call/simple_vad.js',
	'/bitrix/js/im/call/controller.js',
	'/bitrix/js/im/call/engine.js',
	'/bitrix/js/im/call/hardware.js',
	'/bitrix/js/im/call/hardware_dialog.js',
	'/bitrix/js/im/call/abstract_call.js',
	'/bitrix/js/im/call/plain_call.js',
	'/bitrix/js/im/call/voximplant_call.js',
	'/bitrix/js/im/call/util.js',
	'/bitrix/js/im/call/view.js',
	'/bitrix/js/im/call/mic_muted_popup.js',
	'/bitrix/js/im/call/web_screenshare_popup.js',
	'/bitrix/js/im/call/notification.js',
	'/bitrix/js/im/call/notification_conference.js',
	'/bitrix/js/im/call/invite_popup.js',
	'/bitrix/js/im/call/floating_video.js',
	'/bitrix/js/im/call/floating_screenshare.js',
	'/bitrix/js/im/call/logger.js',
	'/bitrix/js/im/call/video_strategy.js',
	'/bitrix/js/im/call/sidebar.js',
	'/bitrix/js/im/call/promo_popup.js',
];

$jsIm = array_merge(
	['/bitrix/js/im/im.js'],
	$jsImCall
);

CJSCore::RegisterExt('im_common', array(
	'js' => '/bitrix/js/im/common.js',
	'css' => ['/bitrix/js/im/css/common.css', '/bitrix/js/im/css/dark_im.css'],
	'lang' => '/bitrix/modules/im/js_common.php',
	'rel' => array('ls', 'ajax', 'date', 'fx', 'user', 'rest.client', 'phone_number', 'loader', 'ui.viewer', 'main.md5', 'im.debug', 'ui.notification')
));

CJSCore::RegisterExt('im_phone_call_view', array(
	'js' => '/bitrix/js/im/phone_call_view.js',
	'css' => array('/bitrix/js/im/css/phone_call_view.css', '/bitrix/components/bitrix/crm.card.show/templates/.default/style.css'),
	'lang' => '/bitrix/modules/im/js_phone_call_view.php',
	'rel' => array('applayout', 'crm_form_loader', 'phone_number')
));

CJSCore::RegisterExt('im_web', array(
	'js' => $jsIm,
	'css' => array(
		'/bitrix/js/im/css/im.css',
		'/bitrix/js/im/css/call/view.css',
		'/bitrix/js/im/css/call/sidebar.css',
		'/bitrix/js/im/css/call/promo-popup.css',
	),
	'lang' => '/bitrix/modules/im/lang/'.LANGUAGE_ID.'/js_im.php',
	'oninit' => function()
	{
		return array(
			'lang_additional' => array(
				'turn_server' => COption::GetOptionString('im', 'turn_server'),
				'turn_server_firefox' => COption::GetOptionString('im', 'turn_server_firefox'),
				'turn_server_login' => COption::GetOptionString('im', 'turn_server_login'),
				'turn_server_password' => COption::GetOptionString('im', 'turn_server_password'),
				'turn_server_max_users' => \Bitrix\Main\Config\Option::get('im', 'turn_server_max_users'),
				'call_server_enabled' => \Bitrix\Im\Call\Call::isCallServerEnabled() ? 'Y' : 'N',
				'call_server_max_users' => \Bitrix\Im\Call\Call::getMaxCallServerParticipants(),
				'call_log_service' => \Bitrix\Im\Call\Call::getLogService(),
				'call_collect_stats' => COption::GetOptionString('im', 'collect_call_stats', 'N'),
				'call_docs_status' => \Bitrix\Im\Integration\Disk\Documents::getDocumentsInCallStatus(),
				'call_resumes_status' => \Bitrix\Im\Integration\Disk\Documents::getResumesOfCallStatus(),
				'jitsi_server' => COption::GetOptionString('im', 'jitsi_server'),
			)
		);
	},
	'rel' => $jsCoreRel
));

CJSCore::RegisterExt('im_page', array(
	'js' => $jsIm,
	'css' => array(
		'/bitrix/js/im/css/im.css',
		'/bitrix/js/im/css/call/view.css',
		'/bitrix/js/im/css/call/sidebar.css',
		'/bitrix/js/im/css/call/promo-popup.css',
	),
	'lang' => '/bitrix/modules/im/js_im.php',
	'oninit' => function()
	{
		return array(
			'lang_additional' => array(
				'turn_server' => COption::GetOptionString('im', 'turn_server'),
				'turn_server_firefox' => COption::GetOptionString('im', 'turn_server_firefox'),
				'turn_server_login' => COption::GetOptionString('im', 'turn_server_login'),
				'turn_server_password' => COption::GetOptionString('im', 'turn_server_password'),
				'turn_server_max_users' => \Bitrix\Main\Config\Option::get('im', 'turn_server_max_users'),
				'call_server_enabled' => \Bitrix\Im\Call\Call::isCallServerEnabled() ? 'Y' : 'N',
				'call_server_max_users' => \Bitrix\Im\Call\Call::getMaxCallServerParticipants(),
				'call_log_service' => \Bitrix\Im\Call\Call::getLogService(),
				'call_collect_stats' => COption::GetOptionString('im', 'collect_call_stats', 'N'),
				'call_docs_status' => \Bitrix\Im\Integration\Disk\Documents::getDocumentsInCallStatus(),
				'call_resumes_status' => \Bitrix\Im\Integration\Disk\Documents::getResumesOfCallStatus(),
				'jitsi_server' => COption::GetOptionString('im', 'jitsi_server'),
			)
		);
	},
	'rel' => $jsCoreRelPage
));

CJSCore::RegisterExt('im_mobile', array(
	'js' => '/bitrix/js/im/mobile.js',
	'lang' => '/bitrix/modules/im/js_mobile.php',
	'rel' => $jsCoreRelMobile
));

CJSCore::RegisterExt('im_mobile_dialog', array(
	'js' => '/bitrix/js/im/mobile_dialog.js',
	'lang' => '/bitrix/modules/im/js_mobile.php',
	'rel' => $jsCoreRelMobile
));

CJSCore::RegisterExt('im_window', array(
	'js' => '/bitrix/js/im/window.js',
	'css' => '/bitrix/js/im/css/window.css',
	'lang' => '/bitrix/modules/im/js_window.php',
	'rel' => Array('popup', 'fx', 'json', 'translit', 'im.component.conference.conference-create', 'ui.alerts'),
));

CJSCore::RegisterExt('im_desktop', array(
	'js' => '/bitrix/js/im/desktop.js',
	'lang' => '/bitrix/modules/im/js_desktop.php',
	'rel' => array('im_page', 'socnetlogdest', 'im.lib.logger'),
));

CJSCore::RegisterExt('im_desktop_utils', array(
	'js' => '/bitrix/js/im/desktop_utils.js',
));

CJSCore::RegisterExt('im_timecontrol', array(
	'js' => '/bitrix/js/im/timecontrol.es6.js',
	'rel' => array('timecontrol'),
));

$imCallRel = ['loader', 'resize_observer', 'webrtc_adapter', 'im.lib.localstorage', 'ui.hint'];
if(Loader::includeModule('voximplant'))
{
	$imCallRel[] = 'voximplant';
}
CJSCore::RegisterExt('im_call', [
	'js' => $jsImCall,
	'css' => [
		'/bitrix/js/im/css/im.css',
		'/bitrix/js/im/css/call/view.css',
		'/bitrix/js/im/css/call/sidebar.css',
		'/bitrix/js/im/css/call/promo-popup.css',
	],
	'rel' => $imCallRel,
	'oninit' => function()
	{
		return array(
			'lang_additional' => array(
				'turn_server' => COption::GetOptionString('im', 'turn_server'),
				'turn_server_firefox' => COption::GetOptionString('im', 'turn_server_firefox'),
				'turn_server_login' => COption::GetOptionString('im', 'turn_server_login'),
				'turn_server_password' => COption::GetOptionString('im', 'turn_server_password'),
				'turn_server_max_users' => \Bitrix\Main\Config\Option::get('im', 'turn_server_max_users'),
				'call_server_enabled' => \Bitrix\Im\Call\Call::isCallServerEnabled() ? 'Y' : 'N',
				'call_server_max_users' => \Bitrix\Main\Config\Option::get('im', 'call_server_max_users'),
				'call_log_service' => \Bitrix\Im\Call\Call::getLogService(),
				'call_collect_stats' => COption::GetOptionString('im', 'collect_call_stats', 'N'),
				'call_docs_status' => \Bitrix\Im\Integration\Disk\Documents::getDocumentsInCallStatus(),
				'call_resumes_status' => \Bitrix\Im\Integration\Disk\Documents::getResumesOfCallStatus(),
				'jitsi_server' => COption::GetOptionString('im', 'jitsi_server'),
			)
		);
	},
]);

$GLOBALS["APPLICATION"]->AddJSKernelInfo('im', array_merge(['/bitrix/js/im/common.js', '/bitrix/js/im/window.js'], $jsIm));
$GLOBALS["APPLICATION"]->AddCSSKernelInfo('im', array('/bitrix/js/im/css/common.css', '/bitrix/js/im/css/window.css', '/bitrix/js/im/css/im.css', '/bitrix/js/im/css/call/view.css', '/bitrix/js/im/css/call/sidebar.css', '/bitrix/js/im/css/call/promo-popup.css'));
?>
