<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (!CModule::IncludeModule('messageservice'))
{
	ShowError(GetMessage('MESSAGESERVICE_MODULE_NOT_INSTALLED'));
	return;
}

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$arResult['isSlider'] = $request->getQuery('IFRAME') === 'Y';
$arResult['providerId'] = isset($_GET['sender']) ? (string)$_GET['sender'] : null;
$arResult['page'] = isset($_GET['page']) ? (string)$_GET['page'] : 'sender';
$this->IncludeComponentTemplate();