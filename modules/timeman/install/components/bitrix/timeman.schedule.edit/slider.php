<?php
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}
$APPLICATION->IncludeComponent(
	'bitrix:main.calendar',
	'',
	['SILENT' => 'Y',],
	null,
	['HIDE_ICONS' => 'Y']
);
$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:timeman.schedule.edit',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
			'SCHEDULE_ID' => \Bitrix\Main\Application::getInstance()->getContext()->getRequest()->get('SCHEDULE_ID'),
		],
	]
);