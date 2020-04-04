<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

if(!CModule::IncludeModule('crm'))
	return;

if(!CModule::IncludeModule('webservice'))
	return;

$arParams['WEBSERVICE_NAME'] = 'bitrix.crm.contact.webservice';
$arParams['WEBSERVICE_CLASS'] = 'CCrmContactWS';
$arParams['WEBSERVICE_MODULE'] = 'crm';

$APPLICATION->IncludeComponent(
	'bitrix:webservice.server',
	'',
	$arParams
);

die();
?>