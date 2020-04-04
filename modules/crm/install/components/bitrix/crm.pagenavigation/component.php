<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/**
 * @var array $arParams
 * @global CMain $APPLICATION
 */
$arParams['URL'] = isset($arParams['URL']) ? $arParams['URL'] : $APPLICATION->GetCurPage();

$this->IncludeComponentTemplate();

