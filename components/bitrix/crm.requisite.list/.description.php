<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	'NAME' => GetMessage('CRM_REQUISITE_LIST_NAME'),
	'DESCRIPTION' => GetMessage('CRM_REQUISITE_LIST_DESCRIPTION'),
	'ICON' => '/images/icon.gif',
	'SORT' => 20,
	'PATH' => array(
		'ID' => 'crm',
		'NAME' => GetMessage('CRM_NAME'),
		'CHILD' => array(
			'ID' => 'requisite',
			'NAME' => GetMessage('CRM_REQUISITE_NAME')
		)
	),
	'CACHE_PATH' => 'Y'
);
?>