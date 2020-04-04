<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	'NAME' => GetMessage('CRM_LEAD_IMPORT_NAME'),
	'DESCRIPTION' => GetMessage('CRM_LEAD_IMPORT_DESCRIPTION'),
	'ICON' => '/images/icon.gif',
	'SORT' => 80,
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