<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if(!CModule::IncludeModule('crm'))
	return false;

$arComponentParameters = Array(
	'PARAMETERS' => array(	
		'FORM_ID' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CRM_WEBFORM_FILL_FORM_ID'),
			'TYPE' => 'STRING',
			'DEFAULT' => '20'
		),
		'FORM_CODE' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CRM_WEBFORM_FILL_FORM_CODE'),
			'TYPE' => 'STRING',
			'DEFAULT' => '20'
		),
		'SECURITY_CODE' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CRM_WEBFORM_FILL_SECURITY_CODE'),
			'TYPE' => 'STRING',
			'DEFAULT' => '20'
		),
		'PATH_TO_INVOICE_PAY' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CRM_WEBFORM_FILL_PATH_TO_INVOICE_PAY'),
			'TYPE' => 'STRING',
			'DEFAULT' => '20'
		),
	)	
);
?>