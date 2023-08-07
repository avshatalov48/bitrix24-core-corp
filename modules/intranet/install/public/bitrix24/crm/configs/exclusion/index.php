<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

/** @var CMain $APPLICATION */
$APPLICATION->IncludeComponent(
	"bitrix:crm.exclusion",
	".default",
	['SEF_FOLDER' => '/crm/configs/exclusion/',]
);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");