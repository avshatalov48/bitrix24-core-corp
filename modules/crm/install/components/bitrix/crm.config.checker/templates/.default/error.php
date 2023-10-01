<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

/** @var CMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */
foreach ($arResult["ERRORS"] as $error)
{
	ShowError($error->getMessage());
}
