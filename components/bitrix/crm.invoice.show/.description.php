<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	'NAME' => GetMessage('CRM_INVOICE_SHOW_NAME'),
	'DESCRIPTION' => GetMessage('CRM_INVOICE_SHOW_DESCRIPTION'),
	'ICON' => '/images/icon.gif',
	'SORT' => 40,
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