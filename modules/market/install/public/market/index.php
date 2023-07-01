<?php

use Bitrix\Main\Loader;
use Bitrix\Market\PageRules;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
GLOBAL $APPLICATION;
$APPLICATION->SetTitle("");

Loader::includeModule('market');
?><?php
$APPLICATION->IncludeComponent(
	"bitrix:ui.sidepanel.wrapper",
	"",
	[
		"POPUP_COMPONENT_NAME" => "bitrix:market",
		"POPUP_COMPONENT_TEMPLATE_NAME" => ".default",
		"POPUP_COMPONENT_PARAMS" => [
			"SEF_MODE" => "Y",
			"SEF_FOLDER" => PageRules::MAIN_PAGE,
			"SEF_URL_TEMPLATES" => [],
			"VARIABLE_ALIASES" => [],
		],
		"USE_UI_TOOLBAR" => "N",
		"PLAIN_VIEW" => "Y",
		"USE_PADDING" => false,
		"PAGE_MODE" => false,
		"USE_BACKGROUND_CONTENT" => false,
	]
);
?><?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>