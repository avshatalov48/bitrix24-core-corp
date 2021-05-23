<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Crm\WebForm\Form;
use Bitrix\Crm\Ads\AdsForm;

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$adsType = $arParams['PROVIDER_TYPE'];
$providers = \Bitrix\Crm\Ads\AdsForm::getProviders([$adsType]);
$arParams['PROVIDER'] = $providers[$adsType];
if (!empty($arParams['PROVIDER']['GROUP']['GROUP_ID']))
{
	$arParams['ACCOUNT_ID'] = $arParams['PROVIDER']['GROUP']['GROUP_ID'];
}
$errorMessage = \Bitrix\Crm\Ads\AdsForm::getTemporaryDisabledMessage($adsType);
if ($errorMessage)
{
	ShowError($errorMessage);
	return;
}

$arResult['DATA'] = array();

$crmFormId = isset($arParams['CRM_FORM_ID']) ? $arParams['CRM_FORM_ID'] : 0;
$crmForm = new Form;
if (!$crmFormId || !$crmForm->loadOnlyForm($crmFormId))
{
	ShowError('Wrong CRM-Form Id.');
	return;
}

/**@var \CUser $USER*/
global $USER;
if (!AdsForm::canUserEdit($USER->GetID()))
{
	ShowError('Access denied.');
	return;
}

$formData = $crmForm->get();

$arResult['DATA']['CRM_FORM_NAME'] = $formData['NAME'];
$arResult['DATA']['CRM_FORM_RESULT_SUCCESS_URL'] = $crmForm->getSuccessPageUrl();

$arResult['LINKS'] = AdsForm::getFormLinks($crmFormId, $arParams['PROVIDER']['TYPE']);
$arResult['HAS_LINKS'] = count($arResult['LINKS']) > 0;

$arResult['ACCOUNT_ID'] = null;
$arResult['LINK_DATE'] = null;
if ($arResult['HAS_LINKS'])
{
	$arResult['ACCOUNT_ID'] = $arResult['LINKS'][0]['ADS_ACCOUNT_ID'];
	$arResult['LINK_DATE'] = $arResult['LINKS'][0]['DATE_INSERT_DISPLAY'];
}

$this->IncludeComponentTemplate();