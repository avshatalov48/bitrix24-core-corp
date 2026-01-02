<?php
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/intranet/public_bitrix24/telephony/sip_office.php');

$APPLICATION->SetTitle(GetMessage('VI_PAGE_SIP_OFFICE_TITLE'));

$APPLICATION->IncludeComponent('bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:voximplant.config.sip',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
			'TYPE' => 'office'
		],
		'USE_PADDING' => false,
		'USE_UI_TOOLBAR' => 'Y',
	]
);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
?>
