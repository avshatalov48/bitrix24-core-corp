<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$APPLICATION->IncludeComponent("bitrix:sender.yandex.toloka", ".default", array(
	'SEF_FOLDER' => '/marketing/toloka/',
	'ONLY_CONNECTOR_FILTERS' => true,
));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>