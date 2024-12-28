<?php

/** @var $APPLICATION \CMain */
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:call.ai',
		'POPUP_COMPONENT_PARAMS' => [
			'CALL_ID' => (int)$request->get('callId'),
		],
		'USE_PADDING' => false,
		'PAGE_MODE' => false,
		'USE_UI_TOOLBAR' => 'Y',
		'CUSTOM_BACKGROUND_STYLE' => 'top left / cover no-repeat url(/bitrix/components/bitrix/call.ai/templates/.default/images/copilot-slider-bg.png)',
	],
);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');
