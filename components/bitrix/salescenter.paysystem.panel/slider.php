<?php
$siteId = '';
if(isset($_REQUEST['site_id']) && is_string($_REQUEST['site_id']))
{
	$siteId = mb_substr(preg_replace('/[^a-z0-9_]/i', '', $_REQUEST['site_id']), 0, 2);
}

if($siteId)
{
	define('SITE_ID', $siteId);
}

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:salescenter.paysystem.panel',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
			'MODE' => $request->get('mode'),
			'PAYSYSTEM_COLOR' => [
				'yandexcheckout' => [
					'bank_card' => '#19D0C8',
					'sberbank' => '#2C9B47',
					'sberbank_sms' => '#289D37',
					'alfabank' => '#EE2A23',
					'yoo_money' => '#FFA900',
					'embedded' => '#0697F2',
				],
				'uapay' => '#E41F18',
				'cash' => '#8EB927',
				'sberbankonline' => '#2C9B47',
				'webmoney' => '#006FA8',
				'qiwi' => '#E9832C',
				'paypal' => '#243B80',
				'liqpay' => '#7AB72B',
			],
			'HIDE_CASH' => $request->get('hideCash'),
		],
		'USE_UI_TOOLBAR' => 'Y',
		'USE_UI_TOOLBAR_MARGIN' => false,
		'USE_PADDING' => false,
	]
);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');