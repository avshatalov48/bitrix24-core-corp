<?php
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/intranet/public_bitrix24/contact_center/index.php');
$APPLICATION->SetTitle(\Bitrix\Main\Localization\Loc::getMessage('TITLE'));

$APPLICATION->IncludeComponent(
	'bitrix:intranet.contact_center.menu.top',
	'',
	[
		'COMPONENT_BASE_DIR' => '/contact_center/',
		'SECTION_ACTIVE' => 'contact_center'
	],
	false
);

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:intranet.contact_center.list',
		"USE_PADDING" => false,
	]
);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');