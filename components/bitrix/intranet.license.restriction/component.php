<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Application;

global $APPLICATION;
$APPLICATION->SetTitle(GetMessage("LICENSE_RESTRICTION_TITLE"));

$arResult["NUM_AVAILABLE_USERS"] = Application::getInstance()->getLicense()->getMaxUsers();
$arResult["NUM_ALL_USERS"] = CUser::GetActiveUsersCount();

$this->IncludeComponentTemplate();
