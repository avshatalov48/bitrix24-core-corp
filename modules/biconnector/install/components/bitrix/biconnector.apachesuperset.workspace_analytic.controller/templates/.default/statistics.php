<?php


if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
	die();
}

use Bitrix\Main\Loader;

/**
 * @var CMain $APPLICATION
 * @var CBitrixComponent $component
 * @var array $arParams
 */

Loader::includeModule('ui');

if ($arParams['COMPONENT_PARAMS']['IFRAME'])
{
	CJSCore::Init("sidepanel");
}

$this->setViewTarget('above_pagetitle');
$APPLICATION->IncludeComponent(
	'bitrix:biconnector.apachesuperset.workspace_analytic.control_panel',
	'',
	[
		'ID' => 'STATISTICS_LIST',
		'ACTIVE_ITEM_ID' => 'STATISTICS_LIST',
	],
	$component
);
$this->endViewTarget();

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:biconnector.usage.stat',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
			'KEY_EDIT_URL' => '/biconnector/key_edit.php?key_id=#ID#',
			'BI_ANALYTIC' => 'Y',
		],
	]
);
