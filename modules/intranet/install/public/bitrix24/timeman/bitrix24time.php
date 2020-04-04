<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

/** @var CMain $APPLICATION */
$APPLICATION->SetTitle('Bitrix24.Time');
$APPLICATION->IncludeComponent("bitrix:faceid.timeman.start", ".default", array());

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");