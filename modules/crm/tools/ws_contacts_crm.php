<?php
define("STOP_STATISTICS", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$arParams['WEBSERVICE_NAME'] = 'bitrix.crm.contact.webservice';
$arParams['WEBSERVICE_CLASS'] = 'CCrmContactWS';
$arParams['WEBSERVICE_MODULE'] = 'crm';

$APPLICATION->IncludeComponent(
	"bitrix:crm.contact.webservice",
	"",
	array()
);

?>