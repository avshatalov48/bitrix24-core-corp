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

$APPLICATION->setTitle(Loc::getMessage('BICONNECTOR_SUPERSET_EXTERNAL_DATASET_CONTROLLER_TITLE_UNUSED_ELEMENTS'));

if ($arParams['COMPONENT_PARAMS']['IFRAME'])
{
	CJSCore::Init('sidepanel');
}

$this->setViewTarget('above_pagetitle');
$APPLICATION->IncludeComponent(
	'bitrix:biconnector.apachesuperset.workspace_analytic.control_panel',
	'',
	[],
	$component
);
$this->endViewTarget();

$APPLICATION->IncludeComponent(
	'bitrix:biconnector.unusedelements',
	'',
	[],
	$component
);
