<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!isset($arParams['MENU_GROUP_ID']))
{
	$arParams['MENU_GROUP_ID'] = $arParams['GROUP_ID'];
}