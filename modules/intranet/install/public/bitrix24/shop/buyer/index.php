<?
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');

$APPLICATION->SetTitle(GetMessage('TITLE'));

$APPLICATION->IncludeComponent(
	'bitrix:crm.order.buyer',
	'.default',
	[
		'SEF_MODE' => 'Y',
		'SEF_FOLDER' => '/shop/buyer/',
		'SEF_URL_TEMPLATES' => [
			'edit' => '#user_id#/edit/'
		],
		'VARIABLE_ALIASES' => [
			'edit' => [],
		]
	]
);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');