<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$APPLICATION->IncludeComponent(
	'bitrix:catalog.store.document.controller',
	'',
	[
		'SEF_MODE' => 'Y',
		'SEF_FOLDER' => '/shop/documents/',
	]
);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
