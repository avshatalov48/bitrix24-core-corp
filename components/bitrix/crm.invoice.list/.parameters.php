<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if(!CModule::IncludeModule('crm'))
	return false;

$arComponentParameters = Array(
	'PARAMETERS' => array(
		'DEAL_COUNT' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CRM_INVOICE_COUNT'),
			'TYPE' => 'STRING',
			'DEFAULT' => '20'
		)							
	)	
);
?>