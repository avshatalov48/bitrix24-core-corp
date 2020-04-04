<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	'NAME' => GetMessage('CRM_WEBFORM_FILL_NAME'),
	'DESCRIPTION' => GetMessage('CRM_WEBFORM_FILL_DESCRIPTION'),
	'ICON' => '/images/icon.gif',
	'SORT' => 20,
	'PATH' => array(
		'ID' => 'crm',
		'NAME' => GetMessage('CRM_NAME'),
		'CHILD' => array(
			'ID' => 'lead',
			'NAME' => GetMessage('CRM_WEBFORM')
		)
	),
	'CACHE_PATH' => 'Y'
);
?>