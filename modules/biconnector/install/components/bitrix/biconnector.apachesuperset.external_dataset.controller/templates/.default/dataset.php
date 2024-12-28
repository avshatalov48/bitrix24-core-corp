<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

/**
 * @var CMain $APPLICATION
 * @var CBitrixComponent $component
 * @var array $arParams
 */

Loader::includeModule('ui');

$APPLICATION->setTitle(Loc::getMessage('BICONNECTOR_SUPERSET_EXTERNAL_DATASET_CONTROLLER_TITLE_DATASET')  ?? '');

if ($arParams['COMPONENT_PARAMS']['IFRAME'])
{
	CJSCore::Init("sidepanel");
}

$this->setViewTarget('above_pagetitle');
$APPLICATION->IncludeComponent(
	'bitrix:biconnector.apachesuperset.external_dataset.control_panel',
	'',
	[
		'ID' => 'EXTERNAL_DATASET_LIST',
		'ACTIVE_ITEM_ID' => 'EXTERNAL_DATASET_LIST',
	],
	$component
);
$this->endViewTarget();

$APPLICATION->IncludeComponent(
	'bitrix:biconnector.apachesuperset.external_dataset.list',
	'',
	[],
	$component
);
