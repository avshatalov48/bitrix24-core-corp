<?
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');

$APPLICATION->SetTitle(GetMessage('TITLE'));

$APPLICATION->IncludeComponent("bitrix:crm.shop.page.controller", "", array(
	"CONNECT_PAGE" => "N",
	"ADDITIONAL_PARAMS" => array(
		"buyer_group_settings" => array(
			"IS_ACTIVE" => true
		)
	)
));

$APPLICATION->IncludeComponent(
	'bitrix:crm.order.buyer_group',
	'.default',
	[
		'SEF_MODE' => 'Y',
		'SEF_FOLDER' => '/shop/buyer_group/',
		'SEF_URL_TEMPLATES' => [
			'list' => '',
			'edit' => '#group_id#/edit/'
		],
		'VARIABLE_ALIASES' => [
			'list' => [],
			'edit' => [],
		]
	]
);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');