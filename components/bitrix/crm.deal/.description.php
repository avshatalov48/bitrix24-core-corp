<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	'NAME' => GetMessage('CRM_DEAL_COMPLEX_NAME'),
	'DESCRIPTION' => GetMessage('CRM_DEAL_COMPLEX_DESCRIPTION'),
	'ICON' => '/images/icon.gif',
	'SORT' => 10,
	'COMPLEX' => 'Y',
	'PATH' => array(
		'ID' => 'crm',
		'NAME' => GetMessage('CRM_NAME'),
		'CHILD' => array(
			'ID' => 'deal',
			'NAME' => GetMessage('CRM_DEAL_NAME')
		)
	),
	'CACHE_PATH' => 'Y'	
);
?>