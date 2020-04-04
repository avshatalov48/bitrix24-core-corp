<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	'NAME' => GetMessage('CRM_INVOICE_COMPLEX_NAME'),
	'DESCRIPTION' => GetMessage('CRM_INVOICE_COMPLEX_DESCRIPTION'),
	'ICON' => '/images/icon.gif',
	'SORT' => 10,
	'COMPLEX' => 'Y',
	'PATH' => array(
		'ID' => 'crm',
		'NAME' => GetMessage('CRM_NAME'),
		'CHILD' => array(
			'ID' => 'invoice',
			'NAME' => GetMessage('CRM_INVOICE_NAME')
		)
	),
	'CACHE_PATH' => 'Y'	
);
?>