<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/settings/configs/vm.php");
$APPLICATION->SetTitle(GetMessage("VM_TITLE"));

$APPLICATION->IncludeComponent("bitrix:intranet.configs.vm", "", array());

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>