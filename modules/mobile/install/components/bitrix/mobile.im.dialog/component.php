<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (isset($_REQUEST['AJAX_CALL']) && $_REQUEST['AJAX_CALL'] == 'Y')
	return;

if (defined('IM_COMPONENT_INIT'))
	return;
else
	define("IM_COMPONENT_INIT", true);

if (intval($USER->GetID()) <= 0)
	return;

if (!CModule::IncludeModule('im'))
	return;

$GLOBALS["APPLICATION"]->SetPageProperty("BodyClass", "im-page");

$arResult = Array();

$arSettings = CIMSettings::Get();
$arResult['SETTINGS'] = $arSettings['settings'];

$CIMMessage = new CIMMessage();
$arResult['MESSAGE'] = $CIMMessage->GetUnreadMessage(Array('USE_TIME_ZONE' => 'N', 'ORDER' => 'ASC'));

$CIMChat = new CIMChat();
$arChatMessage = $CIMChat->GetUnreadMessage(Array('USE_TIME_ZONE' => 'N', 'ORDER' => 'ASC'));
if ($arChatMessage['result'])
{
	foreach ($arChatMessage['message'] as $id => $ar)
	{
		$ar['recipientId'] = 'chat'.$ar['recipientId'];
		$arResult['MESSAGE']['message'][$id] = $ar;
	}

	foreach ($arChatMessage['usersMessage'] as $chatId => $ar)
		$arResult['MESSAGE']['usersMessage']['chat'.$chatId] = $ar;

	foreach ($arChatMessage['unreadMessage'] as $chatId => $ar)
		$arResult['MESSAGE']['unreadMessage']['chat'.$chatId] = $ar;

	foreach ($arChatMessage['users'] as $key => $value)
		$arResult['MESSAGE']['users'][$key] = $value;

	foreach ($arChatMessage['userInGroup'] as $key => $value)
		$arResult['MESSAGE']['userInGroup'][$key] = $value;

	foreach ($arChatMessage['files'] as $key => $value)
		$arResult['MESSAGE']['files'][$key] = $value;

	if ($arParams['DESKTOP'] == 'Y')
	{
		foreach ($arChatMessage['chat'] as $key => $value)
			$arResult['CHAT']['chat'][$key] = $value;
	}
	else
	{
		foreach ($arChatMessage['chat'] as $key => $value)
		{
			$value['fake'] = true;
			$arResult['CHAT']['chat'][$key] = $value;
		}
	}

	foreach ($arChatMessage['userInChat'] as $key => $value)
		$arResult['CHAT']['userInChat'][$key] = $value;

	foreach ($arChatMessage['userChatBlockStatus'] as $key => $value)
		$arResult['CHAT']['userChatBlockStatus'][$key] = $value;
}
if (!isset($arResult['CONTACT_LIST']['users'][$USER->GetID()]))
{
	$arUsers = CIMContactList::GetUserData(array(
		'ID' => $USER->GetID(),
		'DEPARTMENT' => 'N',
		'USE_CACHE' => 'Y',
		'SHOW_ONLINE' => 'N'
	));
	$arResult['CONTACT_LIST']['users'][$USER->GetID()] = $arUsers['users'][$USER->GetID()];
}

$jsInit = array('im_mobile_dialog', 'uploader');
CJSCore::Init($jsInit);

$arResult["ACTION"] = 'DIALOG';
$arResult["CURRENT_TAB"] = isset($_GET['id'])? $_GET['id']: 0;
$arResult["PATH_TO_USER_PROFILE"] = SITE_DIR.'mobile/users/?user_id='.$USER->GetID().'&FROM_DIALOG=Y';
$arResult["PATH_TO_USER_PROFILE_TEMPLATE"] = SITE_DIR.'mobile/users/?user_id=#user_id#&FROM_DIALOG=Y';

$arResult['WEBRTC_MOBILE_SUPPORT'] = \Bitrix\MobileApp\Mobile::getInstance()->isWebRtcSupported();

$arResult['TEMPLATE'] = \Bitrix\Im\Common::objectEncode(
	CIMMessenger::GetMobileDialogTemplateJS(Array(), $arResult)
);

if (!(isset($arParams['TEMPLATE_HIDE']) && $arParams['TEMPLATE_HIDE'] == 'Y'))
	$this->IncludeComponentTemplate();

return $arResult;

?>