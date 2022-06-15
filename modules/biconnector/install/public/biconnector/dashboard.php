<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:biconnector.dashboard',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
			'SET_TITLE' => 'Y',
			'DASHBOARD_ID' => $_GET['id'],
		],
		'POPUP_COMPONENT_USE_BITRIX24_THEME' => 'Y',
		'PADDING_USE' => false,
		'PLAIN_VIEW' => false,
	]
);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
