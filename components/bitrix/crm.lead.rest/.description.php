<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	'NAME' => GetMessage('CRM_LEAD_REST_NAME'),
	'DESCRIPTION' => GetMessage('CRM_LEAD_REST_DESCRIPTION'),
	'ICON' => '/images/icon.gif',
	'SORT' => 100,
	'PATH' => array(
		'ID' => 'crm',
		'NAME' => GetMessage('CRM_NAME'),
		'CHILD' => array(
			'ID' => 'lead',
			'NAME' => GetMessage('CRM_LEAD_NAME')
		)
	),
	'CACHE_PATH' => 'Y'
);
?>