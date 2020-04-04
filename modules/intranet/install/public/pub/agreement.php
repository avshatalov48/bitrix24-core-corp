<?php
define('SKIP_TEMPLATE_AUTH_ERROR', true);
define('NOT_CHECK_PERMISSIONS', true);

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php';

$APPLICATION->SetPageProperty("BodyClass", "flexible-middle-width");
\Bitrix\Main\Page\Asset::getInstance()->addString('<meta name="viewport" content="width=device-width, initial-scale=1.0">');

$APPLICATION->IncludeComponent(
	"bitrix:main.userconsent.view",
	"",
	array(),
	null,
	array("HIDE_ICONS" => "Y")
);

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php';
