<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	'NAME' => GetMessage('CRM_DEAL_EDIT_NAME'),
	'DESCRIPTION' => GetMessage('CRM_DEAL_EDIT_DESCRIPTION'),
	'ICON' => '/images/icon.gif',
	'SORT' => 30,
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