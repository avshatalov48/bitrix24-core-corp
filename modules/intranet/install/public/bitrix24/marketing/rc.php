<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$APPLICATION->IncludeComponent("bitrix:sender.rc", ".default", array(
	'SEF_FOLDER' => '/marketing/rc/',
	'PATH_TO_SEGMENT_ADD' => '/marketing/segment/edit/0/',
	'PATH_TO_SEGMENT_EDIT' => '/marketing/segment/edit/#id#/',
));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");