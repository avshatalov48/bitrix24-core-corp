<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
$accessToken = \Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getQuery('access_token');

$arResult['PROFILE_LINK'] = "/bitrix/tools/dav_profile.php?action=payload&params[resources]=caldav&params[access_token]=" . $accessToken;

$this->IncludeComponentTemplate();
?>