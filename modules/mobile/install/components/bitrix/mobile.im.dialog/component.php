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

$jsInit = array('im_mobile_dialog', 'uploader', 'mobile.pull.client');
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