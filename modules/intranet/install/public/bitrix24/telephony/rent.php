<?php
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/intranet/public_bitrix24/telephony/rent.php');

$APPLICATION->SetTitle(GetMessage('VI_PAGE_RENT_TITLE'));

\Bitrix\Main\Loader::includeModule('voximplant');

$account = new CVoxImplantAccount();
$accountLang = $account->GetAccountLang(false);

if (in_array($accountLang, ['ua', 'kz'])) {
	$APPLICATION->IncludeComponent('bitrix:ui.sidepanel.wrapper',
		'',
		[
			'POPUP_COMPONENT_NAME' => 'bitrix:voximplant.config.rent.order',
			'POPUP_COMPONENT_TEMPLATE_NAME' => '',
			'USE_PADDING' => false,
			'USE_UI_TOOLBAR' => 'Y',
		]
	);
} else {
	$APPLICATION->IncludeComponent('bitrix:ui.sidepanel.wrapper',
		'',
		[
			'POPUP_COMPONENT_NAME' => 'bitrix:voximplant.config.rent',
			'POPUP_COMPONENT_TEMPLATE_NAME' => '',
			'USE_PADDING' => false,
			'USE_UI_TOOLBAR' => 'Y',
		]
	);
}

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
?>
