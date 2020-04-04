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

if (!CModule::IncludeModule('mobileapp'))
	return;

$GLOBALS["APPLICATION"]->SetPageProperty("BodyClass", "im-page");
if (isset($arParams['TEMPLATE_POPUP']) && $arParams['TEMPLATE_POPUP'] == 'Y')
{
	$GLOBALS["APPLICATION"]->SetPageProperty("Viewport", "user-scalable=no, initial-scale=1.0, maximum-scale=1.0, width=290");
}
else if (isset($arParams['POSITION']) && $arParams['POSITION'] == 'RIGHT')
{
	$GLOBALS["APPLICATION"]->SetPageProperty("BodyClass", "im-page im-page-right");
}

$arResult = Array();

CIMContactList::SetOnline();

$arSettings = CIMSettings::Get();
$arResult['SETTINGS'] = $arSettings['settings'];

$CIMContactList = new CIMContactList();
$arResult['CONTACT_LIST'] = $CIMContactList->GetList();

foreach ($arResult['CONTACT_LIST']['chats'] as $key => $value)
{
	$value['fake'] = true;
	$arResult['CHAT']['chat'][$key] = $value;
}

$arRecent = CIMContactList::GetRecentList(Array(
	'LOAD_LAST_MESSAGE' => 'Y',
	'USE_TIME_ZONE' => 'N',
	'USE_SMILES' => 'N'
));
$arResult['RECENT'] = Array();

$CIMMessage = new CIMMessage();
$arResult['MESSAGE'] = $CIMMessage->GetUnreadMessage(Array('USE_TIME_ZONE' => 'N', 'ORDER' => 'ASC'));
$arResult["MESSAGE_COUNTER"] = $arResult['MESSAGE']['countMessage']; // legacy

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

	foreach ($arChatMessage['chat'] as $key => $value)
	{
		$value['fake'] = true;
		$arResult['CHAT']['chat'][$key] = $value;
	}

	foreach ($arChatMessage['userInChat'] as $key => $value)
		$arResult['CHAT']['userInChat'][$key] = $value;

	foreach ($arChatMessage['userChatBlockStatus'] as $key => $value)
		$arResult['CHAT']['userChatBlockStatus'][$key] = $value;
}
$arResult['MESSAGE']['flashMessage'] = CIMMessage::GetFlashMessage($arResult['MESSAGE']['unreadMessage']);
$arResult["MESSAGE_COUNTER"] = $arResult['MESSAGE']['countMessage']+$arChatMessage['countMessage']; // legacy
foreach ($arRecent as $userId => $value)
{
	if ($value['TYPE'] == IM_MESSAGE_CHAT || $value['TYPE'] == IM_MESSAGE_OPEN)
	{
		if (!isset($arResult['CHAT']['chat'][$value['CHAT']['id']]))
		{
			$value['CHAT']['fake'] = true;
			$arResult['CHAT']['chat'][$value['CHAT']['id']] = $value['CHAT'];
		}
		$value['MESSAGE']['userId'] = $userId;
		$value['MESSAGE']['recipientId'] = $userId;
	}
	else
	{
		$arResult['CONTACT_LIST']['users'][$value['USER']['id']] = $value['USER'];
		$value['MESSAGE']['userId'] = $userId;
		$value['MESSAGE']['recipientId'] = $userId;
	}
	$arResult['RECENT'][] = $value['MESSAGE'];
}

// Merge message users with contact list
if (isset($arResult['MESSAGE']['users']) && !empty($arResult['MESSAGE']['users']))
{
	foreach ($arResult['MESSAGE']['users'] as $arUser)
		$arResult['CONTACT_LIST']['users'][$arUser['id']] = $arUser;

	if (isset($arResult['MESSAGE']['userInGroup']))
	{
		foreach ($arResult['MESSAGE']['userInGroup'] as $arUserInGroup)
		{
			if (isset($arResult['CONTACT_LIST']['userInGroup'][$arUserInGroup['id']]['users']))
				$arResult['CONTACT_LIST']['userInGroup'][$arUserInGroup['id']]['users'] = array_unique(array_merge($arResult['CONTACT_LIST']['userInGroup'][$arUserInGroup['id']]['users'], $arUserInGroup['users']));
			else
			{
				if (isset($arResult['CONTACT_LIST']['userInGroup']['other']['users']))
					$arResult['CONTACT_LIST']['userInGroup']['other']['users'] = array_unique(array_merge($arResult['CONTACT_LIST']['userInGroup']['other']['users'], $arUserInGroup['users']));
				else
				{
					$arUserInGroup['id'] = 'other';
					$arResult['CONTACT_LIST']['userInGroup']['other'] = $arUserInGroup;
				}
			}
		}
	}
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

$jsInit = array('im_mobile');
CJSCore::Init($jsInit);

$arResult['BOT'] = \Bitrix\Im\Bot::getListForJs();
$arResult['COMMAND'] = \Bitrix\Im\Command::getListForJs();

$arResult["ACTION"] = 'RECENT';

$phoneStatus = CIMMessenger::CheckPhoneStatus();
if ($phoneStatus)
{
	if (\Bitrix\MobileApp\Mobile::getInstance()->isLarge())
	{
		$arResult['CALL_METHOD'] = 'telephony';
	}
	else
	{
		$arResult['CALL_METHOD'] = 'combined';
	}
}
else
{
	$arResult['CALL_METHOD'] = 'device';
}

$arResult['WEBRTC_MOBILE_SUPPORT'] = \Bitrix\MobileApp\Mobile::getInstance()->isWebRtcSupported();

if (!(isset($arParams['TEMPLATE_HIDE']) && $arParams['TEMPLATE_HIDE'] == 'Y'))
	$this->IncludeComponentTemplate();

return $arResult;

?>