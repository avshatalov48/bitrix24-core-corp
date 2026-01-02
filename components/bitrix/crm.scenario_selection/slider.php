<?php

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');

if (\Bitrix\Main\Loader::includeModule('ui'))
{
	\Bitrix\UI\Toolbar\Facade\Toolbar::deleteFavoriteStar();
}

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:crm.scenario_selection',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [],
		'USE_PADDING' => false,
		'USE_UI_TOOLBAR' => 'Y',
	],
);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');
