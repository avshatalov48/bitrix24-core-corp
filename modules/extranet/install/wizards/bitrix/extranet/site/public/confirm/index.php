<?php

define('CONFIRM_PAGE', true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/extranet/public/confirm/index.php');

$APPLICATION->SetTitle(GetMessage("EXTRANET_CONFIRM_PAGE_TITLE"));
?>
<?php
$APPLICATION->IncludeComponent(
	"bitrix:system.auth.initialize",
	"",
	[
		'CHECKWORD_VARNAME' => 'checkword',
		'USERID_VARNAME' => 'user_id',
		'AUTH_URL' => SITE_DIR . 'auth.php',
	],
	false
);
?>
<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
