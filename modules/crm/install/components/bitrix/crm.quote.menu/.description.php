<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	'NAME' => GetMessage('CRM_QUOTE_MENU_NAME'),
	'DESCRIPTION' => GetMessage('CRM_QUOTE_MENU_DESCRIPTION'),
	'ICON' => '/images/icon.gif',
	'SORT' => 50,
	'PATH' => array(
		'ID' => 'crm',
		'NAME' => GetMessage('CRM_NAME'),
		'CHILD' => array(
			'ID' => 'quote',
			'NAME' => GetMessage('CRM_QUOTE_NAME')
		)
	),
	'CACHE_PATH' => 'Y'
);
?>