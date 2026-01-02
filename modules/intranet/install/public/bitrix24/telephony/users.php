<?php
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/intranet/public/telephony/users.php');

$APPLICATION->SetTitle(GetMessage('VI_PAGE_USERS_TITLE'));

$APPLICATION->IncludeComponent('bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:voximplant.numbers',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'USE_PADDING' => false,
		'USE_UI_TOOLBAR' => 'Y',
	]
);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
?>

