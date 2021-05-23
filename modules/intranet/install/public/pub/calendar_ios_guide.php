<?php
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php';
define('SKIP_TEMPLATE_AUTH_ERROR', true);
define('NOT_CHECK_PERMISSIONS', true);

$APPLICATION->SetPageProperty("BodyClass", "flexible-mode--linear-blue");
$APPLICATION->AddHeadString('<meta name="viewport" content="width=device-width, initial-scale=1.0">');

$APPLICATION->IncludeComponent(
	"bitrix:intranet.calendar.iosguide",
	"",
	array(),
	null,
	array()
);

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php';
