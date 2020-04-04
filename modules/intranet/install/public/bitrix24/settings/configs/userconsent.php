<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/settings/configs/userconsent.php");
$APPLICATION->SetTitle(GetMessage("USER_CONSENT_TITLE"));

$APPLICATION->IncludeComponent("bitrix:intranet.userconsent", "", array());

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");