<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
global $APPLICATION;

$APPLICATION->IncludeComponent(
	'bitrix:main.interface.filter',
	'new',
	array(
		'GRID_ID'=>$arParams['~GRID_ID'],
		'FILTER'=>$arParams['~FILTER'],
		"FILTER_PRESETS"=>$arParams["~FILTER_PRESETS"],
		'FILTER_ROWS'=>$arParams['~FILTER_ROWS'],
		'FILTER_FIELDS'=>$arParams['~FILTER_FIELDS'],
		'OPTIONS'=>$arParams['~OPTIONS'],
		'FILTER_INFO'=>$arResult['FILTER_INFO'],
		'RENDER_FILTER_INTO_VIEW'=>isset($arParams['~RENDER_FILTER_INTO_VIEW']) ? $arParams['~RENDER_FILTER_INTO_VIEW'] : '',
		'HIDE_FILTER'=>isset($arParams['~HIDE_FILTER']) ? $arParams['~HIDE_FILTER'] : false,
		'ENABLE_PROVIDER'=>isset($arParams['~ENABLE_PROVIDER']) ? $arParams['~ENABLE_PROVIDER'] : false,
		'NAVIGATION_BAR'=>isset($arParams['~NAVIGATION_BAR']) ? $arParams['~NAVIGATION_BAR'] : array()
	),
	$component,
	array('HIDE_ICONS'=>true)
);
