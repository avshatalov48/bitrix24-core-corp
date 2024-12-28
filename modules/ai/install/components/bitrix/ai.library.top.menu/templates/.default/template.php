<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}
/** @var CMain $APPLICATION*/
/** @var array $arResult*/
/** @var array $arParams*/

global $APPLICATION;

$APPLICATION->includeComponent(
	'bitrix:main.interface.buttons',
	'',
	array(
		'ID' => 'libraries',
		'ITEMS' => $arResult['menuItems'],
		"EDIT_MODE" => false,
	)
);
