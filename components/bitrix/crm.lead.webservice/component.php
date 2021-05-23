<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

if(!CModule::IncludeModule('crm'))
	return;

if(!CModule::IncludeModule('webservice'))
	return;

$arParams['WEBSERVICE_NAME'] = 'bitrix.crm.lead.webservice';
$arParams['WEBSERVICE_CLASS'] = 'CCrmLeadWS';
$arParams['WEBSERVICE_MODULE'] = '';

$APPLICATION->IncludeComponent(
	'bitrix:webservice.server',
	'',
	$arParams
);

die();
?>