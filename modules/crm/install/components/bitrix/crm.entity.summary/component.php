<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

// 'Fileman' module always installed
//CModule::IncludeModule('fileman');
$ID = $arResult['ID'] = isset($arParams['ID']) ? $arParams['ID'] : '';
$arResult['CONTAINER_ID'] = $ID;
$options = CUserOptions::GetOption('crm.entity.summary', strtolower($ID));
if(!$options)
{
	$options = array('isFolded' => 'Y');
}
$arResult['IS_FOLDED'] = isset($options['isFolded']) && $options['isFolded'] === 'Y';
$arResult['TITLE'] = isset($arParams['~TITLE']) && is_array($arParams['~TITLE']) ? $arParams['~TITLE'] : array();
if(!isset($arResult['TITLE']['VALUE']))
{
	$arResult['TITLE']['VALUE'] = '';
}
if(!isset($arResult['TITLE']['EDITABLE']))
{
	$arResult['TITLE']['EDITABLE'] = false;
}

$arResult['LEGEND'] = isset($arParams['~LEGEND']) ? $arParams['~LEGEND'] : '';
$arResult['BLOCKS'] = isset($arParams['BLOCKS']) && is_array($arParams['BLOCKS']) ? $arParams['BLOCKS'] : array();
$arResult['EDITOR_ID'] = isset($arParams['EDITOR_ID']) ? $arParams['EDITOR_ID'] : '';

//$arResult['IS_LOCKED'] = isset($arParams['IS_LOCKED']) ? $arParams['IS_LOCKED'] : false;
//$arResult['LOCK_LEGEND'] = isset($arParams['LOCK_LEGEND']) ? $arParams['LOCK_LEGEND'] : '';
$arResult['LOCK_CONTROL_DATA'] = isset($arParams['LOCK_CONTROL_DATA']) && is_array($arParams['LOCK_CONTROL_DATA'])
	? $arParams['LOCK_CONTROL_DATA'] : array('ENABLED' => false);

if(!isset($arResult['LOCK_CONTROL_DATA']['ENABLED']))
{
	$arResult['LOCK_CONTROL_DATA']['ENABLED'] = true;
}

if(!isset($arResult['LOCK_CONTROL_DATA']['IS_LOCKED']))
{
	$arResult['LOCK_CONTROL_DATA']['IS_LOCKED'] = false;
}

if(!isset($arResult['LOCK_CONTROL_DATA']['LOCK_LEGEND']))
{
	$arResult['LOCK_CONTROL_DATA']['LOCK_LEGEND'] = '';
}

if(!isset($arResult['LOCK_CONTROL_DATA']['UNLOCK_LEGEND']))
{
	$arResult['LOCK_CONTROL_DATA']['UNLOCK_LEGEND'] = '';
}

if(!isset($arResult['LOCK_CONTROL_DATA']['EDITABLE']))
{
	$arResult['LOCK_CONTROL_DATA']['EDITABLE'] = false;
}

if(!isset($arResult['LOCK_CONTROL_DATA']['FIELD_ID']))
{
	$arResult['LOCK_CONTROL_DATA']['FIELD_ID'] = '';
}

$arResult['SIP'] = isset($arParams['SIP']) && is_array($arParams['SIP']) ? $arParams['SIP'] : array();

$this->IncludeComponentTemplate();
