<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arResult['IS_CLOUD'] = \Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24');

if ($arParams['PAGE'] == 'result')
{
	$type = (!empty($arResult['LAST_DATA']) && isset($arResult['LAST_DATA']['type']) ? $arResult['LAST_DATA']['type'] : '');
	$arResult['LAST_DATA_TYPE_DESCRIPTION'] = \Bitrix\Intranet\Component\UserProfile\StressLevel::getTypeDescription($type, $arResult['LAST_DATA']['value']);
	$arResult['LAST_DATA_TYPE_TEXT'] = \Bitrix\Intranet\Component\UserProfile\StressLevel::getValueDescription($type, $arResult['LAST_DATA']['value']);
	$arResult['LAST_DATA_TYPE_TEXT_TITLE'] = \Bitrix\Intranet\Component\UserProfile\StressLevel::getTypeTextTitle($type, $arResult['LAST_DATA']['value']);
}
?>