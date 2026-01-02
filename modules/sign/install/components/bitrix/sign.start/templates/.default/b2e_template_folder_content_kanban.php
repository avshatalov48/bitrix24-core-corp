<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var CMain $APPLICATION */
/** @var array $arParams */

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:sign.b2e.employee.template.list',
		'POPUP_COMPONENT_PARAMS' => [
			'ENTITY_ID' => \Bitrix\Main\Engine\CurrentUser::get()->getId(),
			'URL_LIST_FOR_RELOAD' => $arParams['URL_LIST_FOR_RELOAD_TEMPLATE_GRID'] ?? [],
		],
		'USE_UI_TOOLBAR' => 'Y',
	],
	$this->getComponent(),
);
