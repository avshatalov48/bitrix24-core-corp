<?php

$siteID = isset($_REQUEST['site'])? mb_substr(preg_replace('/[^a-z0-9_]/i', '', $_REQUEST['site']), 0, 2) : '';
if ($siteID !== '')
{
	define('SITE_ID', $siteID);
}

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!\Bitrix\Main\Loader::includeModule('crm'))
{
	die();
}

$componentData = isset($_REQUEST['PARAMS']) && is_array($_REQUEST['PARAMS']) ? $_REQUEST['PARAMS'] : [];
$componentParams = [];
if (isset($componentData['signedParameters']))
{
	$componentParams = \CCrmInstantEditorHelper::unsignComponentParams(
		(string)$componentData['signedParameters'],
		'crm.deal.list'
	);
	if (is_null($componentParams))
	{
		ShowError('Wrong component signed parameters');
		die();
	}
}

global $APPLICATION;
$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:crm.deal.list',
		'POPUP_COMPONENT_TEMPLATE_NAME' => $componentData['template'] ?? '',
		'POPUP_COMPONENT_PARAMS' => $componentParams,
		'USE_UI_TOOLBAR' => 'Y',
		'POPUP_COMPONENT_USE_BITRIX24_THEME' => 'Y',
	]
);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
