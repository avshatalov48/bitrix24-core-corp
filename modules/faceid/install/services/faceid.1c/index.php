<?

define("NOT_CHECK_FILE_PERMISSIONS", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Localization\Loc;

$APPLICATION->IncludeComponent(
	"bitrix:faceid.1c",
	"",
	Array()
);

CMain::FinalActions();