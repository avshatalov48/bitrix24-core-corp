<?php
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:salescenter.control_panel',
		"USE_PADDING" => false,
	]
);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');