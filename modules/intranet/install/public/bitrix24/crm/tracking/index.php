<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$APPLICATION->IncludeComponent("bitrix:crm.tracking", ".default", array(
	'SEF_FOLDER' => '/crm/tracking/',
));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");