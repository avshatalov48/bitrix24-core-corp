<?php
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');

\Bitrix\Main\Loader::includeModule('crm');


$APPLICATION->ShowHead();

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:crm.config.terminal.settings',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'USE_PADDING' => false,
		'PAGE_MODE' => false,
		'USE_UI_TOOLBAR' => 'N',
	]
);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');