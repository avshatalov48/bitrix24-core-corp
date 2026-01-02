<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

$arResult['SHOW_FORM'] = !(is_array($arParams['~AUTH_RESULT']) && $arParams['~AUTH_RESULT']['TYPE'] === 'OK');