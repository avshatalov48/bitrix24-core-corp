<?
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');

$APPLICATION->SetTitle(GetMessage('TITLE'));

$APPLICATION->IncludeComponent(
	'bitrix:crm.order.import.instagram',
	'.default',
	[
		'SEF_MODE' => 'Y',
		'SEF_FOLDER' => '/shop/import/instagram/',
		'SEF_URL_TEMPLATES' => [
			'view' => '',
			'edit' => 'edit/'
		],
		'VARIABLE_ALIASES' => [
			'view' => [],
			'edit' => [],
		],
		'INDIVIDUAL_USE' => 'N',
	]
);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');