<?php

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

/** @var $APPLICATION \CMain */
$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
			'POPUP_COMPONENT_NAME' => 'bitrix:booking.booking.detail',
			'POPUP_COMPONENT_TEMPLATE_NAME' => '',
			'POPUP_COMPONENT_PARAMS' => [
				'ID' => $_GET['id'] ?? 0,
			],
			'USE_UI_TOOLBAR' => 'Y',
			'POPUP_COMPONENT_USE_BITRIX24_THEME' => 'Y',
			'PLAIN_VIEW' => true,
		],
);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
