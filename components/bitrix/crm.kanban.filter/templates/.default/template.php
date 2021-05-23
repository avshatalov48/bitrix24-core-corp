<?php if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

use \Bitrix\Crm\Kanban\Helper;

\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/interface_grid.js');

$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.filter',
	'title',
	$arResult['filterParams'],
	$component,
	['HIDE_ICONS' => true]
);