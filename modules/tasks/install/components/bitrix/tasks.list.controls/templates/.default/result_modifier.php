<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arResult['BX24_RU_ZONE'] = \Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24') && preg_match("/^(ru)_/", COption::GetOptionString("main", "~controller_group_name", ""));