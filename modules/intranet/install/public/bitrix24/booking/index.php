<?php

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

/** @var $APPLICATION \CMain */
$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:booking',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'USE_UI_TOOLBAR' => 'Y',
		'PLAIN_VIEW' => true,
	],
);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
