<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (isset($_REQUEST['AJAX_CALL']) && $_REQUEST['AJAX_CALL'] == 'Y')
	return;

if (intval($USER->GetID()) <= 0)
	return;

if (!CModule::IncludeModule('im'))
	return;

if (!CModule::IncludeModule('pull'))
	return;

if (!CModule::IncludeModule('mobile'))
	return;

if (!CModule::IncludeModule('mobileapp'))
	return;

CJSCore::Init(array("mobile_webrtc", "im_mobile"));

if (!(isset($arParams['TEMPLATE_HIDE']) && $arParams['TEMPLATE_HIDE'] == 'Y'))
	$this->IncludeComponentTemplate();

return $arResult;

?>