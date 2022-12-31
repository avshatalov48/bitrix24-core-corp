<?
require($_SERVER["DOCUMENT_ROOT"]."/mobile/headers.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$APPLICATION->IncludeComponent("bitrix:immobile.chat.card", ".default", array(),false, Array("HIDE_ICONS" => "Y"));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php")
?>