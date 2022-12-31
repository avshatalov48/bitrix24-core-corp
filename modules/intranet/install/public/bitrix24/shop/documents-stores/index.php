<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$APPLICATION->IncludeComponent(
	'bitrix:catalog.store.entity.controller',
	'',
	[
		'SEF_MODE' => 'Y',
		'SEF_FOLDER' => '/shop/documents-stores/',
	]
);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
