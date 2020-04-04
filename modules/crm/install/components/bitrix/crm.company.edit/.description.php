<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	'NAME' => GetMessage('CRM_COMPANY_EDIT_NAME'),
	'DESCRIPTION' => GetMessage('CRM_COMPANY_EDIT_DESCRIPTION'),
	'ICON' => '/images/icon.gif',
	'SORT' => 30,
	'PATH' => array(
		'ID' => 'crm',
		'NAME' => GetMessage('CRM_NAME'),
		'CHILD' => array(
			'ID' => 'company',
			'NAME' => GetMessage('CRM_COMPANY_NAME')
		)
	),
	'CACHE_PATH' => 'Y'
);
?>