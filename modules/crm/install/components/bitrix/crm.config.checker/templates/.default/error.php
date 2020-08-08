<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

/** @var CAllMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */
foreach ($arResult["ERRORS"] as $error)
{
	ShowError($error->getMessage());
}
