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
		'POPUP_COMPONENT_NAME' => 'bitrix:salescenter.control_panel',
		'USE_PADDING' => false,
		'USE_UI_TOOLBAR' => 'Y',
	]
);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');
