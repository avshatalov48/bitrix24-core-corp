<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$APPLICATION->IncludeComponent("bitrix:im.conference.center", ".default", array(
	'SEF_FOLDER' => '/conference/',
));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");