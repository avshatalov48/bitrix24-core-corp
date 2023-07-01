<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CBXFeatures::IsFeatureEnabled('timeman') || !CModule::IncludeModule('timeman'))
	return;

if (intval($arParams["AVATAR_SIZE"] ?? 0) <= 0)
{
	$arParams["AVATAR_SIZE"] = 100;
}

$arParams['USER']['PHOTO'] = $arParams['USER']['PERSONAL_PHOTO'] > 0
	? CIntranetUtils::InitImage($arParams['USER']['PERSONAL_PHOTO'], $arParams["AVATAR_SIZE"], 0, BX_RESIZE_IMAGE_EXACT)
	: array();
$arParams['MANAGER']['PHOTO'] = $arParams['MANAGER']['PERSONAL_PHOTO'] > 0
	? CIntranetUtils::InitImage($arParams['MANAGER']['PERSONAL_PHOTO'], $arParams["AVATAR_SIZE"], 0, BX_RESIZE_IMAGE_EXACT)
	: array();

$userPhotoSrc = isset($arParams['USER']['PHOTO']['CACHE']) ? $arParams['USER']['PHOTO']['CACHE']['src'] : '';
$managerPhotoSrc = isset($arParams['MANAGER']['PHOTO']['CACHE']) ? $arParams['MANAGER']['PHOTO']['CACHE']['src'] : '';
$arParams['USER']['PHOTO'] = $userPhotoSrc;
$arParams['MANAGER']['PHOTO'] = $managerPhotoSrc;

if ($this->getTemplateName() === 'mobile')
{
	$this->setSiteTemplateId('mobile_app');
}

$this->IncludeComponentTemplate();
?>