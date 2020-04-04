<?php
define("PUBLIC_AJAX_MODE", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define("NOT_CHECK_PERMISSIONS", true);
define("DisableEventsCheck", true);
define("NO_AGENT_CHECK", true);

/* PORTALS BITRIX24 -> CONTROLLER -> PROVIDER */

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
header('Content-Type: application/json; charset=UTF-8');

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/transformer/handlers/transformer_result.php");

CMain::FinalActions();
die();