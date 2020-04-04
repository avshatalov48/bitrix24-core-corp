<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	'NAME' => GetMessage('CRM_CONTACT_WEBSERVICE_NAME'),
	'DESCRIPTION' => GetMessage('CRM_CONTACT_WEBSERVICE_DESCRIPTION'),
	'ICON' => '/images/icon.gif',
	'SORT' => 90,
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