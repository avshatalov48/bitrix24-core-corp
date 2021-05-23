<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	'NAME' => GetMessage('CRM_CONTACT_SHOW_NAME'),
	'DESCRIPTION' => GetMessage('CRM_CONTACT_SHOW_DESCRIPTION'),
	'ICON' => '/images/icon.gif',
	'SORT' => 40,
	'PATH' => array(
		'ID' => 'crm',
		'NAME' => GetMessage('CRM_NAME'),
		'CHILD' => array(
			'ID' => 'contact',
			'NAME' => GetMessage('CRM_CONTACT_NAME')
		)
	),
	'CACHE_PATH' => 'Y'
);
?>