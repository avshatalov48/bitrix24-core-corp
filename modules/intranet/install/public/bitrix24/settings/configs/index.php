<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/settings/configs/index.php");
$APPLICATION->SetTitle(GetMessage("CONFIG_TITLE_1"));

$APPLICATION->IncludeComponent("bitrix:intranet.settings", "", array());

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>