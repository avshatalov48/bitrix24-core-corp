<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arResult */
/** @var CMain $APPLICATION */

$id = $arResult['VARIABLES']['routeId'];
$buttons = [
	'save',
	'cancel',
];
if ($id > 0)
{
	$buttons[] = 'remove';
}

$APPLICATION->IncludeComponent("bitrix:ui.sidepanel.wrapper", "", [
	'POPUP_COMPONENT_NAME' => 'bitrix:crm.communicationchannel.rule.details',
	'POPUP_COMPONENT_TEMPLATE_NAME' => '',
	'POPUP_COMPONENT_PARAMS' => [
		'ID' => $arResult['VARIABLES']['routeId'],
	],
	'USE_PADDING' => false,
	'PAGE_MODE' => false,
	'PAGE_MODE_OFF_BACK_URL' => '/crm/configs/communication_channel_routes/',
	'BUTTONS' => $buttons,
	//'PLAIN_VIEW' => true,
	'USE_BACKGROUND_CONTENT' => false,

	'RELOAD_PAGE_AFTER_SAVE' => true,
	'CLOSE_AFTER_SAVE' => true,
]);
