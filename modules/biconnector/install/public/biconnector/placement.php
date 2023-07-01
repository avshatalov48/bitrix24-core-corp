<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @var CMain $APPLICATION */

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:biconnector.placement',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
			'SET_TITLE' => 'Y',
			'PLACEMENT_ID' => $_GET['id'],
		],
		'POPUP_COMPONENT_USE_BITRIX24_THEME' => 'Y',
		'PADDING_USE' => false,
		'PLAIN_VIEW' => false,
	]
);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
