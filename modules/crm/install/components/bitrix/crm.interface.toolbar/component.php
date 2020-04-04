<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if(!is_array($arParams['BUTTONS']))
	$arParams['BUTTONS'] = array();

$arParams['TOOLBAR_ID'] = isset($arParams['TOOLBAR_ID']) && $arParams['TOOLBAR_ID'] !== ''
	? preg_replace('/[^a-z0-9_]/i', '', $arParams['TOOLBAR_ID'])
	: 'toolbar_'.(strtolower(randString(5)));

$this->IncludeComponentTemplate();


