<?php
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}
$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:timeman.worktime.record.report',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
			'RECORD_ID' => \Bitrix\Main\Application::getInstance()->getContext()->getRequest()->get('RECORD_ID'),
		]
	]
);